<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-scheduler for the canonical source repository
 */

/**
 * Task interface
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
interface ComSchedulerTaskInterface
{
    const TASK_COMPLETE = 0;
    const TASK_SUSPEND  = -1;

    const FREQUENCY_EVERY_MINUTE       = '* * * * *';
    const FREQUENCY_EVERY_QUARTER_HOUR = '*/15 * * * *';
    const FREQUENCY_EVERY_HALF_HOUR    = '*/30 * * * *';
    const FREQUENCY_HOURLY             = '0 * * * *';
    const FREQUENCY_DAILY              = '0 0 * * *';
    const FREQUENCY_WEEKLY             = '0 0 * * 0';
    const FREQUENCY_MONTHLY            = '0 0 1 * *';
    const FREQUENCY_YEARLY             = '0 0 1 1 *';

    /**
     * Runs the task
     *
     * @return int The result of $this->complete() or $this->suspend()
     */
    public function run();

    /**
     * Logs a message for debugging purposes
     *
     * @param $message string
     */
    public function log($message);

    /**
     * Returns if the task has time left to run.
     * If the method returns false the task should save state and call suspend as soon as possible.
     *
     * Condition is passed by the dispatcher, usually only when the task is run in an HTTP context
     *
     * @return boolean
     */
    public function hasTimeLeft();

    /**
     * Returns the remaining time for the task to run
     *
     * @return int
     */
    public function getTimeLeft();

    /**
     * Signals the task completion
     *
     * @return int
     */
    public function complete();

    /**
     * Signals the task suspension
     *
     * @return int
     */
    public function suspend();

    /**
     * Returns the prioritized flag of the task
     *
     * @return bool
     */
    public function isPrioritized();

    /**
     * Set tif the task is prioritized
     * @param $prioritized bool
     * @return $this
     */
    public function setPrioritized($prioritized);

    /**
     * Returns the task frequency in cron expression
     *
     * @return string
     */
    public function getFrequency();

    /**
     * Returns the task state
     *
     * @return KObjectConfigInterface
     */
    public function getState();
}