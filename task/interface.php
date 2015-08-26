<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

/**
 * Task interface
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
interface ComSchedulerTaskInterface
{
    const PRIORITY_HIGH    = 2;
    const PRIORITY_LOW     = 4;

    const TASK_COMPLETE = 0;
    const TASK_SUSPEND  = -1;

    /**
     * Runs the task
     *
     * @return int The result of $this->complete() or $this->suspend()
     */
    public function run();

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
     * Returns the task priority
     *
     * @return int
     */
    public function getPriority();

    /**
     * Returns the task frequency in minutes
     *
     * @return int
     */
    public function getFrequency();

    /**
     * Returns the task state
     *
     * @return KObjectConfigInterface
     */
    public function getState();
}