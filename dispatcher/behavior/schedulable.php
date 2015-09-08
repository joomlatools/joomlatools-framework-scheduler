<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-scheduler for the canonical source repository
 */

/**
 * Schedulable behavior
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
class ComSchedulerDispatcherBehaviorSchedulable extends KControllerBehaviorAbstract
{
    /**
     * Set defaults
     *
     * @param KObjectConfig $config
     */
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'jobs'      => array(),
            'trigger_condition' => null
        ));

        parent::_initialize($config);
    }

    /**
     * Runs the job dispatcher and ends the request if the request has scheduler=1
     *
     * @param KDispatcherContextInterface $context
     * @return bool
     * @throws Exception
     */
    protected function _beforeDispatch(KDispatcherContextInterface $context)
    {
        if ($context->request->query->has('scheduler'))
        {
            try
            {
                $this->syncJobs();

                $dispatcher = $this->getObject('com:scheduler.controller.dispatcher');
                $dispatcher->dispatch();

                $result = new stdClass();
                $result->continue = (bool) $dispatcher->pickNextJob();
                /* @todo replace with Koowa::getInstance()->isDebug when koowa 3.0 is out */
                $result->logs     = KClassLoader::getInstance()->isDebug() ? $dispatcher->getLogs() : array();

                $context->response->setContent(json_encode($result), 'application/json');
                $this->send($context);

                return false;
            }
            catch (Exception $e)
            {
                /* @todo replace with Koowa::getInstance()->isDebug when koowa 3.0 is out */
                if (KClassLoader::getInstance()->isDebug()) {
                    throw $e;
                }
            }
        }

        return true;
    }

    /**
     * Adds the Javascript trigger code to the current view output
     *
     * @param KDispatcherContextInterface $context
     * @throws Exception
     */
    protected function _beforeGet(KDispatcherContextInterface $context)
    {
        try
        {
            if ($context->getRequest()->getFormat() === 'html')
            {
                $view      = $this->getController()->getView();
                $condition = $this->getConfig()->trigger_condition;

                if ($view instanceof KViewHtml && is_callable($condition) && $condition($context))
                {
                    // Create URL and encode using encodeURIComponent standards
                    $url = $this->getObject('request')->getUrl()->setQuery(array('scheduler' => 1, 'format' => 'json'), true);
                    $url = strtr(rawurlencode($url), array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'));

                    $html = '<script data-inline
                         data-scheduler='.$url.'
                         type="text/javascript"
                         src="media://koowa/com_scheduler/js/request.js"></script>';

                    $html = $this->getObject('com:scheduler.view.default.html')->getTemplate()
                        ->loadString($html, 'php')
                        ->render();

                    $view->addCommandCallback('after.render', function($context) use ($html) {
                        $context->result .= $html;
                    });
                }
            }
        }
        catch (Exception $e)
        {
            /* @todo replace with Koowa::getInstance()->isDebug when koowa 3.0 is out */
            if (KClassLoader::getInstance()->isDebug()) {
                throw $e;
            }
        }
    }
}