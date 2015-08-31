<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

class ComSchedulerViewBehaviorSchedulable extends KViewBehaviorAbstract
{
    protected function _afterRender(KViewContextInterface $context)
    {
        $view = KObjectManager::getInstance()->getObject('com:scheduler.view.default.html');
        $template = $view->getTemplate();

        $code = $template->loadFile('com:scheduler.behavior.schedule.html')
            ->render(array(
                'url'        => (string)$this->getRoute(array('component' => 'docman', 'scheduler' => 1, 'format' => 'json')),
                'csrf_token' => $this->getObject('user')->getSession()->getToken()
            ));

        $context->result .= $code;
    }
}