<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

class ComSchedulerTaskClear_cache extends ComSchedulerTaskAbstract
{
    protected function _initialize(KObjectConfig $config)
    {
        $config->append(array(
            'priority' => ComSchedulerTaskInterface::PRIORITY_HIGH,
            'frequency' => 30
        ));
    }

    public function run()
    {
        var_dump('clear cache');

        return $this->complete();
    }
}