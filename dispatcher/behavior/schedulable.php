<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

/**
 * Schedulable behavior
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
class ComSchedulerDispatcherBehaviorSchedulable extends KDispatcherBehaviorAbstract
{
    /**
     * @throws RuntimeException
     * @param KObjectConfig $config
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        if (!$this->getConfig()->table_name) {
            throw new RuntimeException('Tasks table name cannot be empty');
        }
    }

    /**
     * Set defaults
     *
     * @param KObjectConfig $config
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'table_name' => null,
            'tasks'      => array(),
            'model'      => 'com:scheduler.model.tasks',
            'trigger_condition' => null
        ));

        parent::_initialize($config);
    }

    /**
     * Runs the scheduler or adds the JavaScript trigger
     * @param KDispatcherContextInterface $context
     * @return bool
     * @throws Exception
     */
    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        try {
            if ($context->request->query->has('scheduler'))
            {
                $this->_runTaskDispatcher($context);

                return false;
            }

            if ($context->getRequest()->getFormat() === 'html' && $context->getRequest()->isGet())
            {
                $trigger_condition = $this->getConfig()->trigger_condition;

                if (is_callable($trigger_condition) && $trigger_condition($context)) {
                    $this->_addTrigger($context);
                }
            }
        }
        catch (Exception $e)
        {
            /* @todo replace with Koowa::getInstance()->isDebug when koowa 3.0 is out */
            if (KClassLoader::getInstance()->isDebug()) {
                throw $e;
            }
        }

        return true;
    }

    /**
     * Runs the task dispatcher and ends the request
     *
     * @param KDispatcherContextInterface $context
     */
    protected function _runTaskDispatcher(KDispatcherContextInterface $context)
    {
        $dispatcher = $this->getTaskDispatcher();
        $dispatcher->dispatch();

        $result = new stdClass();
        $result->continue = (bool) $dispatcher->pickNextTask();
        /* @todo replace with Koowa::getInstance()->isDebug when koowa 3.0 is out */
        $result->logs     = KClassLoader::getInstance()->isDebug() ? $dispatcher->getLogs() : array();

        $context->response->setContent(json_encode($result), 'application/json');

        $this->send($context);
    }

    /**
     * Adds the Javascript trigger code to the current view output
     *
     * @param KDispatcherContextInterface $context
     */
    protected function _addTrigger(KDispatcherContextInterface $context)
    {
        $url = $this->getObject('request')->getUrl()->setQuery(array('scheduler' => 1, 'format' => 'json'), true);
        // encodeURIComponent replacement
        $url = strtr(rawurlencode($url), array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'));

        $html = '<script data-inline
                         data-scheduler='.$url.'
                         type="text/javascript"
                         src="assets://scheduler/js/request.js"></script>';

        $html = $this->getObject('com:scheduler.view.default.html')->getTemplate()
            ->loadString($html, 'php')
            ->render();

        $this->getController()->getView()->addCommandCallback('after.render', function($context) use ($html) {
            $context->result .= $html;
        });

        $this->syncTasks();
    }

    /**
     * Returns the task dispatcher
     *
     * @return ComSchedulerTaskDispatcherInterface
     */
    public function getTaskDispatcher()
    {
        $config = $this->getConfig();

        return $this->getObject('com:scheduler.task.dispatcher', array(
            'model' => $this->getObject($config->model, array(
                'table' => $this->getObject('com:scheduler.database.table.tasks', array('name' => $config->table_name))
            ))
        ));
    }

    /**
     * Syncs the tasks passed into the object config to the database
     *
     * Automatically creates the database table if necessary
     * Also handles task frequency updates
     */
    public function syncTasks()
    {
        try {
            $model = $this->getTaskDispatcher()->getModel();
        }
        catch (RuntimeException $e)
        {

            $adapter = $this->getObject('database.adapter.mysqli');
            $content = file_get_contents(__DIR__.'/../../resources/install/template.sql');
            $content = sprintf($content, $adapter->getTablePrefix().$this->getConfig()->table_name);
            $adapter->execute($content);

            $model = $this->getTaskDispatcher()->getModel();
        }

        $tasks    = $this->getConfig()->tasks->toArray();
        $existing = $model->fetch();

        foreach ($tasks as $identifier => $config)
        {
            if (is_numeric($identifier)) {
                $identifier = $config;
                $config = array();
            }

            $entity = $existing->find($identifier);

            if (!$entity)
            {
                $entity = $model->create();
                $entity->id = $identifier;
            }

            $entity->frequency = $this->getObject($identifier, $config)->getFrequency();
            $entity->save();
        }
    }
}