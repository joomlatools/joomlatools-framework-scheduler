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
abstract class ComSchedulerTaskAbstract extends KObject implements ComSchedulerTaskInterface
{
    /**
     * Prioritized flag
     *
     * @var bool
     */
    protected $_prioritized;

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

    /**
     * A logger passed by the task dispatcher
     *
     * @var callable
     */
    protected $_logger;

    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->_prioritized  = $config->prioritized;
        $this->_state        = $config->state;
        $this->_frequency    = $config->frequency;
        $this->_logger       = KObjectConfig::unbox($config->logger);

        if (!$this->_state instanceof KObjectConfig) {
            $this->_state = new KObjectConfig($this->_state);
        }
    }

    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'state'       => array(),
            'prioritized' => false,
            'frequency'   => ComSchedulerTaskInterface::FREQUENCY_HOURLY
        ));
    }

    /**
     * Runs the task
     *
     * @return int The result of $this->complete() or $this->suspend()
     */
    abstract public function run();

    /**
     * Logs a message for debugging purposes
     *
     * @param $message string
     */
    public function log($message)
    {
        if (is_callable($this->_logger)) {
            call_user_func($this->_logger, $message, $this);
        }
    }

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
     * Returns the prioritized flag of the task
     *
     * @return bool
     */
    public function isPrioritized()
    {
        return $this->_prioritized;
    }

    /**
     * Set tif the task is prioritized
     * @param $prioritized bool
     * @return $this
     */
    public function setPrioritized($prioritized)
    {
        $this->_prioritized = (bool) $prioritized;

        return $this;
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