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
class ComSchedulerTaskDispatcher extends KObject implements ComSchedulerTaskDispatcherInterface
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

        @set_time_limit(60);
        @ini_set('memory_limit', '256M');
        @ignore_user_abort(true);
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
            $task = $this->_getFirstRunnableTask();

            if (!$task) {
                return;
            }

            $time = time();
            $runner = $this->getObject($task->id, array(
                'state'       => $task->getState(),
                'should_stop' => function() use ($time) {
                    return time() > $time+5;
                }
            ));

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

    public function isDue($task)
    {
        $result = true;

        if ($task->completed_on !== '0000-00-00 00:00:00')
        {
            $cron = Cron\CronExpression::factory($task->frequency);

            $result = $cron->getNextRunDate($task->completed_on) < new DateTime('now');
        }

        return $result;
    }

    public function canRun()
    {
        $this->_quitStaleTasks();

        $result = !$this->getModel()->status(1)->count();

        if ($result) {
            $result = (bool)$this->_getFirstRunnableTask();
        }

        return $result;
    }

    protected function _quitStaleTasks()
    {
        $stale = $this->getModel()->stale(1)->fetch();

        if (count($stale))
        {
            foreach ($stale as $entity) {
                $entity->status = 0;
            }

            $stale->save();
        }
    }

    protected function _getFirstRunnableTask()
    {
        $high_priority = $this->getModel()->status(0)->sort('ordering')->queue(1)->fetch();

        if (count($high_priority) === 0)
        {
            $low_priority = $this->getModel()->status(0)->sort('ordering')->queue(0)->fetch();

            foreach ($low_priority as $task)
            {
                if ($this->isDue($task))
                {
                    $task->queue = 1;
                    $task->ordering = -PHP_INT_MAX;

                    if ($task->save()) {
                        return $task;
                    }
                }
            }
        }
        else {
            foreach ($high_priority as $task)
            {
                if ($this->isDue($task))
                {
                    return $task;
                }
            }
        }

        return null;
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