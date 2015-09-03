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
class ComSchedulerTaskDispatcher extends ComSchedulerTaskDispatcherAbstract
{
    /**
     * @param KObjectConfig $config
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        @set_time_limit(60);
        @ini_set('memory_limit', '256M');
        @ignore_user_abort(true);
    }

    /**
     * Dispatches the next task in line
     *
     * @return bool
     */
    public function dispatch()
    {
        if ($task = $this->pickNextTask())
        {
            $result = false;

            $runner = $this->getObject($task->id, array(
                'state'       => $task->getState(),
                'stop_on'     => time()+15,
                'logger'      => array($this, 'log')
            ));

            // Set to running
            $task->status = 1;
            $task->save();

            try
            {
                $this->log('dispatch task', $runner);

                try {
                    $result = $runner->run();
                }
                catch (Exception $e)
                {
                    $this->log('exception thrown: '.$e->getMessage(), $runner);

                    $result = ComSchedulerTaskInterface::TASK_COMPLETE;
                }

                $this->log('result: '.$result, $runner);

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

            if ($result === ComSchedulerTaskInterface::TASK_COMPLETE && !$this->_getNextRun($task)) {
                $task->delete();
            }
            else {
                // Stop the task
                $task->status = 0;
                $task->save();
            }

            return true;
        }

        return false;
    }

    /**
     * Picks the next task to run based on priority
     *
     * @return null|KDatabaseRowInterface
     */
    public function pickNextTask()
    {
        $this->_quitStaleTasks();

        if ($this->getModel()->status(1)->count() === 0)
        {
            $high_priority = $this->getModel()->status(0)->sort('ordering')->queue(1)->fetch();

            if (count($high_priority) === 0)
            {
                $low_priority = $this->getModel()->status(0)->sort('ordering')->queue(0)->fetch();

                foreach ($low_priority as $task)
                {
                    if ($this->_isDue($task))
                    {
                        $task->queue = 1;

                        if ($task->save()) {
                            return $task;
                        }
                    }
                }
            }
            else
            {
                foreach ($high_priority as $task)
                {
                    if ($this->_isDue($task)) {
                        return $task;
                    }
                }
            }
        }



        return null;
    }

    protected function _isDue($task)
    {
        $result = true;

        if ($task->completed_on !== '0000-00-00 00:00:00')
        {
            try {
                $cron   = Cron\CronExpression::factory($task->frequency);
                $result = $cron->getNextRunDate($task->completed_on) < new DateTime('now');
            }
            catch (RuntimeException $e) {
                $result = true; // last run and it'll be deleted
            }
            catch (Exception $e) {
                $result = false;
            }
        }

        return $result;
    }

    protected function _getNextRun($task)
    {
        $result = false;

        try {
            $cron   = Cron\CronExpression::factory($task->frequency);
            $result = $cron->getNextRunDate();
        }
        catch (RuntimeException $e) {
            // never gonna run again :(
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
}