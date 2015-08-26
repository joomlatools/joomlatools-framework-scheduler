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
            'frequency' => 60
        ));
    }

    /**
     * Runs the task
     *
     * @return int The result of $this->complete() or $this->suspend()
     */
    abstract public function run();

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
     * Returns the task frequency in minutes
     *
     * @return int
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