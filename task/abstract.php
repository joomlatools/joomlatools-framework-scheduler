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
abstract class ComSchedulerTaskAbstract extends KObject implements ComSchedulerTaskInterface
{
    /**
     * Task priority
     *
     * @var int
     */
    protected $_priority;

    /**
     * Task state
     *
     * @var KObjectConfigInterface
     */
    protected $_state;

    /**
     * Task frequency
     *
     * @var int
     */
    protected $_frequency;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_priority = $config->priority;

        $this->_state = $config->state;

        $this->_frequency = $config->frequency;
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'state'    => array(),
            'priority' => ComSchedulerTaskInterface::PRIORITY_LOW,
            'frequency' => ComSchedulerTaskInterface::FREQUENCY_HOURLY
        ));
    }

    /**
     * Runs the task
     *
     * @return int The result of $this->complete() or $this->suspend()
     */
    abstract public function run();

    /**
     * Returns if the task has time left to run.
     * If the method returns false the task should save state and call suspend as soon as possible.
     *
     * Condition is passed by the dispatcher, usually only when the task is run in an HTTP context
     *
     * @return boolean
     */
    public function hasTimeLeft()
    {
        $result = true;

        if ($this->getConfig()->stop_on) {
            $result = time() < $this->getConfig()->stop_on;
        }

        return $result;
    }

    /**
     * Returns the remaining time for the task to run
     *
     * @return int
     */
    public function getTimeLeft()
    {
        return max($this->getConfig()->stop_on - time(), 0);
    }

    /**
     * Signals the task completion
     *
     * @return int
     */
    public function complete()
    {
        return ComSchedulerTaskInterface::TASK_COMPLETE;
    }

    /**
     * Signals the task suspension
     *
     * @return int
     */
    public function suspend()
    {
        return ComSchedulerTaskInterface::TASK_SUSPEND;
    }

    /**
     * Returns the task priority
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * Returns the task frequency in cron expression
     *
     * @return string
     */
    public function getFrequency()
    {
        return $this->_frequency;
    }

    /**
     * Returns the task state
     *
     * @return KObjectConfigInterface
     */
    public function getState() {
        return $this->_state;
    }
}