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
abstract class ComSchedulerTaskDispatcherAbstract extends KObject implements ComSchedulerTaskDispatcherInterface
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

    abstract public function dispatch();

    abstract public function pickNextTask();

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

    /**
     * Logs a message for debugging purposes
     *
     * @param $message
     * @param $task KObjectInterface|null
     */
    public function log($message, $task = null)
    {
        $identifier = $task ? (string) $task->getIdentifier() : 'dispatcher';

        if (!isset($this->_logs[$identifier])) {
            $this->_logs[$identifier] = array();
        }

        $this->_logs[$identifier][] = (object) array('message' => $message, 'timestamp' => time());
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