<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-scheduler for the canonical source repository
 */

/**
 * Job dispatcher
 *
 * Runs the jobs by ordering and priority
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
abstract class ComSchedulerJobDispatcherAbstract extends KObject implements ComSchedulerJobDispatcherInterface
{
    /**
     * @var KModelInterface
     */
    protected $_model;

    /**
     * @var array
     */
    protected $_logs = array();

    /**
     * @param KObjectConfig $config
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->setModel($config->model);
    }

    /**
     * Dispatches the next job in line
     *
     * @return bool
     */
    abstract public function dispatch();

    /**
     * Picks the next job to run based on priority
     *
     * @return null|KDatabaseRowInterface
     */
    abstract public function pickNextJob();

    /**
     * @param KObjectConfig $config
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'model' => 'com:scheduler.model.jobs'
        ));

        parent::_initialize($config);
    }

    /**
     * Logs a message for debugging purposes
     *
     * @param $message
     * @param $job KObjectInterface|null
     */
    public function log($message, $job = null)
    {
        $identifier = $job ? (string) $job->getIdentifier() : 'dispatcher';

        if (!isset($this->_logs[$identifier])) {
            $this->_logs[$identifier] = array();
        }

        $this->_logs[$identifier][] = $message;
    }

    /**
     * Returns the logs
     *
     * @return array
     */
    public function getLogs()
    {
        return $this->_logs;
    }

    /**
     * Returns the current model after resetting its state
     *
     * @return KModelInterface
     */
    public function getModel()
    {
        $this->_model->getState()->reset();

        return $this->_model;
    }

    /**
     * Sets the model
     *
     * @param $model string|KModelInterface
     * @return $this
     */
    public function setModel($model)
    {
        if(!$model instanceof KModelInterface) {
            $model = $this->getObject($model);
        }

        $this->_model = $model;

        return $this;
    }

}