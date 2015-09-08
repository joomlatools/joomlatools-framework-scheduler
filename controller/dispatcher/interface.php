<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-scheduler for the canonical source repository
 */

/**
 * Dispatcher interface
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
interface ComSchedulerControllerDispatcherInterface extends KControllerInterface
{
    /**
     * Get the controller model
     *
     * @throws  UnexpectedValueException    If the model doesn't implement the ModelInterface
     * @return	KModelInterface
     */
    public function getModel();

    /**
     * Set the controller model
     *
     * @param   mixed   $model An object that implements ObjectInterface, ObjectIdentifier object
     *                         or valid identifier string
     * @return	KControllerInterface
     */
    public function setModel($model);
}