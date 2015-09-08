<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-scheduler for the canonical source repository
 */

/**
 * Job behavior
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 *
 * @method void run(ComSchedulerJobInterface $job)
 */
class ComSchedulerControllerDispatcher extends KControllerAbstract implements ComSchedulerControllerDispatcherInterface
{
    /**
     * Model object or identifier (com://APP/COMPONENT.model.NAME)
     *
     * @var	string|object
     */
    protected $_model;

    /**
     * @param KObjectConfig $config
     */
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        @set_time_limit(60);
        @ini_set('memory_limit', '256M');
        @ignore_user_abort(true);

        // Set the model identifier
        $this->_model = $config->model;
    }

    /**
     * Initializes the default configuration for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param   KObjectConfig $config Configuration options
     * @return void
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'model'	=> 'com:scheduler.model.jobs'
        ));

        parent::_initialize($config);
    }

    /**
     * Gets the job context
     *
     * @return ComSchedulerJobContextInterface
     */
    public function getContext()
    {
        $context = new ComSchedulerJobContext();
        $context->setSubject($this);
        $context->setRequest($this->getRequest());
        $context->setResponse($this->getResponse());
        $context->setUser($this->getUser());

        return $context;
    }

    /**
     * Runs a job
     *
     * @param ComSchedulerJobContextInterface $context
     * @return int
     */
    protected function _actionRun(ComSchedulerJobContextInterface $context)
    {
        if (!$context->param instanceof ComSchedulerJobInterface) {
            throw new UnexpectedValueException('Invalid job passed. Expecting ComSchedulerJobInterface');
        }

        /** @var ComSchedulerJobInterface $job */
        $job = $context->param;

        try {
            $context->result = $job->run($context);
        }
        catch (Exception $e)
        {
            $context->log('exception thrown: '.$e->getMessage());

            $context->result = ComSchedulerJobInterface::JOB_FAIL;
        }

        return $context->result;
    }

    protected function _beforeRun(ComSchedulerJobContextInterface $context)
    {
        if ($context->param instanceof ComSchedulerJobInterface) {
            $context->log('running '.(string)$context->param->getIdentifier());
        }
    }

    protected function _afterRun(ComSchedulerJobContextInterface $context)
    {
        if ($context->param instanceof ComSchedulerJobInterface) {
            $context->log('ran '.(string)$context->param->getIdentifier());
            $context->log('result: '.$context->result);
        }
    }

    protected function _afterDispatch(ComSchedulerJobContextInterface $context)
    {
        var_dump($context->result);
        var_dump($context->getLogs());
    }

    /**
     * Dispatches the next job in line
     *
     * @param ComSchedulerJobContextInterface $context
     * @return bool
     */
    protected function _actionDispatch(ComSchedulerJobContextInterface $context)
    {
        if ($entity = $this->pickNextJob())
        {
            // Set to running
            $entity->status = 1;
            $entity->save();

            try
            {
                $context->setTimeLimit(time()+15);
                $context->setState($entity->getState());

                $context->param  = $this->getObject($entity->id);

                $this->execute('run', $context);

                /*
                complete:
                    high priority: put it on the top of low priority queue
                    low priority:  put it on the bottom of low priority queue
                suspend:
                    high priority: put it on the top of high priority queue
                    low priority:  put it on the bottom of high priority queue
                */

                $entity->ordering = $context->param->isPrioritized() ? -PHP_INT_MAX : PHP_INT_MAX;

                if ($context->result === ComSchedulerJobInterface::JOB_SUSPEND) {
                    $entity->queue = 1;
                }
                else {
                    $entity->completed_on = gmdate('Y-m-d H:i:s');
                    $entity->queue = 0;
                }
            }
            catch (Exception $e) {}

            if ($context->result === ComSchedulerJobInterface::JOB_COMPLETE && !$this->_getNextRun($entity)) {
                $entity->delete();
            }
            else {
                // Stop the job
                $entity->status = 0;
                $entity->save();
            }
        }

        return $context->result;
    }


    /**
     * Picks the next job to run based on priority
     *
     * @return null|KDatabaseRowInterface
     */
    public function pickNextJob()
    {
        $this->_quitStaleJobs();

        if ($this->getModel()->status(1)->count() === 0)
        {
            $high_priority = $this->getModel()->status(0)->sort('ordering')->queue(1)->fetch();

            if (count($high_priority) === 0)
            {
                $low_priority = $this->getModel()->status(0)->sort('ordering')->queue(0)->fetch();

                foreach ($low_priority as $job)
                {
                    if ($this->_isDue($job))
                    {
                        $job->queue = 1;

                        if ($job->save()) {
                            return $job;
                        }
                    }
                }
            }
            else
            {
                foreach ($high_priority as $job)
                {
                    if ($this->_isDue($job)) {
                        return $job;
                    }
                }
            }
        }



        return null;
    }

    protected function _isDue($job)
    {
        $result = true;

        if ($job->completed_on !== '0000-00-00 00:00:00')
        {
            try {
                $cron   = Cron\CronExpression::factory($job->frequency);
                $result = $cron->getNextRunDate($job->completed_on) < new DateTime('now');
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

    protected function _getNextRun($job)
    {
        $result = false;

        try {
            $cron   = Cron\CronExpression::factory($job->frequency);
            $result = $cron->getNextRunDate();
        }
        catch (RuntimeException $e) {
            // never gonna run again :(
        }

        return $result;
    }

    protected function _quitStaleJobs()
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


    /**
     * Get the model object attached to the controller
     *
     * @throws  \UnexpectedValueException   If the model doesn't implement the ModelInterface
     * @return  KModelInterface
     */
    public function getModel()
    {
        if(!$this->_model instanceof KModelInterface)
        {
            //Make sure we have a model identifier
            if(!($this->_model instanceof KObjectIdentifier)) {
                $this->setModel($this->_model);
            }

            $this->_model = $this->getObject($this->_model);

            if(!$this->_model instanceof KModelInterface)
            {
                throw new UnexpectedValueException(
                    'Model: '.get_class($this->_model).' does not implement KModelInterface'
                );
            }

            //Inject the request into the model state
            $this->_model->setState($this->getRequest()->query->toArray());
        }

        $this->_model->getState()->reset();

        return $this->_model;
    }

    /**
     * Method to set a model object attached to the controller
     *
     * @param   mixed   $model An object that implements KObjectInterface, KObjectIdentifier object
     *                         or valid identifier string
     * @return	KControllerView
     */
    public function setModel($model)
    {
        if(!($model instanceof KModelInterface))
        {
            if(is_string($model) && strpos($model, '.') === false )
            {
                // Model names are always plural
                if(KStringInflector::isSingular($model)) {
                    $model = KStringInflector::pluralize($model);
                }

                $identifier         = $this->getIdentifier()->toArray();
                $identifier['path'] = array('model');
                $identifier['name'] = $model;

                $identifier = $this->getIdentifier($identifier);
            }
            else $identifier = $this->getIdentifier($model);

            $model = $identifier;
        }

        $this->_model = $model;

        return $this->_model;
    }
}