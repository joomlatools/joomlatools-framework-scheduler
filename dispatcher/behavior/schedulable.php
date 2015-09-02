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
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'tasks' => array(
                'com:scheduler.task.clear_cache',
                'com:scheduler.task.foobar' => array('frequency' => '* * * * * *'),
                'com:scheduler.task.create_documents'
            ),
            'model'      => 'com:scheduler.model.tasks',
            'table_name' => 'docman_tasks',
            'condition'  => function($context) {
                return $context->request->query->view === 'documents';
            }
        ));

        parent::_initialize($config);
    }

    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        try {
            $condition = $this->getConfig()->condition;

            if ($context->request->query->has('scheduler'))
            {
                $dispatcher = $this->getTaskDispatcher();
                $dispatcher->dispatch();

                $result = new stdClass();
                $result->continue = (bool) $dispatcher->pickNextTask();
                /* @todo replace with Koowa::getInstance()->isDebug when koowa 3.0 is out */
                $result->logs     = KClassLoader::getInstance()->isDebug() ? $dispatcher->getLogs() : array();

                $context->response->setContent(json_encode($result), 'application/json');
                $this->getMixer()->send($context);

                return false;
            }
            else if ($context->request->getFormat() === 'html' && (is_callable($condition) && $condition($context)))
            {
                $javascript = $this->_renderJavascript();
                $this->getController()->getView()->addCommandCallback('after.render', function($context) use ($javascript) {
                    $context->result .= $javascript;
                });
                $this->syncTasks();
            }
        }
        catch (Exception $e) {
            die($e->getMessage());
        }
    }

    protected function _renderJavascript()
    {
        $url = $this->getObject('request')->getUrl()->setQuery(array('scheduler' => 1, 'format' => 'json'), true);
        // encodeURIComponent replacement
        $url = strtr(rawurlencode($url), array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'));

        $html = '<script data-inline
                         data-scheduler='.$url.'
                         type="text/javascript"
                         src="assets://scheduler/js/request.js"></script>';

        $code = $this->getObject('com:scheduler.view.default.html')->getTemplate()
            ->loadString($html, 'php')
            ->render();

        return $code;
    }

    /**
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