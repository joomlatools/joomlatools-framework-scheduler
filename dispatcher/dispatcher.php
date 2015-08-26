<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

/**
 * Task dispatcher
 *
 * Runs the tasks by ordering and priority
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
class ComSchedulerDispatcher extends KObject implements ComSchedulerDispatcherInterface
{
    /**
     * @var KModelInterface
     */
    protected $_model;

    /**
     * @param KObjectConfig $config
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setModel($config->model);
    }

    /**
     * @param KObjectConfig $config
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'model' => 'com:scheduler.model.tasks'
        ));

        parent::_initialize($config);
    }

    public function run()
    {
        if ($this->canRun())
        {
            $task   = $this->_getFirstRunnableTask();

            if ($task->isNew()) {
                return;
            }

            $runner = $this->getObject($task->id, array('state' => $task->getState()));

            $task->status = 1;
            $task->save();

            try
            {
                $result = $runner->run();

                /*
                complete:
                    high priority: put it on the top of low priority queue
                    low priority:  put it on the bottom of low priority queue
                suspend:
                    high priority: put it on the top of high priority queue
                    low priority:  put it on the bottom of high priority queue
                */
                $high_priority = $runner->getPriority() === ComSchedulerTaskInterface::PRIORITY_HIGH;

                $task->ordering = $high_priority ? -PHP_INT_MAX : PHP_INT_MAX;

                if ($result === ComSchedulerTaskInterface::TASK_SUSPEND) {
                    $task->queue = 1;
                }
                else {
                    $task->completed_on = gmdate('Y-m-d H:i:s');
                    $task->queue = 0;
                }
            }
            catch (Exception $e) {
            }

            $task->status = 0;
            $task->save();
        }
    }

    public function canRun()
    {
        $this->_quitStaleTasks();

        $result = !$this->getModel()->status(1)->count();

        if ($result) {
            $result = $this->getModel()->due(1)->status(0)->count();
        }

        return $result;
    }

    protected function _quitStaleTasks()
    {
        $active = $this->getModel()->status(1)->fetch();

        foreach ($active as $task)
        {
            $modified = new DateTime($task->modified_on);

            // check if the task has been running for more than 2 minutes
            if (time() - $modified->format('U') > 120) {
                $task->status = 0;
                $task->save();
            }
        }
    }

    protected function _getFirstRunnableTask()
    {
        $state = array(
            'queue'  => 1,
            'due'    => 1,
            'status' => 0,
            'limit'  => 1,
            'sort'   => 'ordering'
        );

        $task = $this->getModel()->setState($state)->fetch();

        if ($task->isNew())
        {
            $low = $this->getModel()->setState($state)->queue(0)->fetch();

            if (!$low->isNew())
            {
                $low->queue = 1;
                $low->ordering = -PHP_INT_MAX;

                if ($low->save()) {
                    $task = $low;
                }
            }
        }

        return $task;
    }

    public function getModel()
    {
        $this->_model->getState()->reset();

        return $this->_model;
    }

    public function setModel($model)
    {
        if(!$model instanceof KModelInterface) {
            $model = $this->getObject($model);
        }

        $this->_model = $model;

        return $this;
    }
}