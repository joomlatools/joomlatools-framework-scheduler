<?php

/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

class ComSchedulerTaskCreate_documents extends ComSchedulerTaskAbstract
{
    public function run()
    {
        $state = $this->getState();
        $queue = KObjectConfig::unbox($state->queue);

        $this->log('docs in queue: '.count($queue));

        if (empty($queue))
        {
            $queue = array(1,2,3,4,5);

            $this->log('created queue');
        }
        elseif (is_array($queue))
        {
            while ($this->hasTimeLeft() && count($queue))
            {
                $first = array_shift($queue);

                $this->log('create doc'.$first);
                sleep(2);
            }

        }

        $state->queue = $queue;

        return empty($queue) ? $this->complete() : $this->suspend();
    }
}