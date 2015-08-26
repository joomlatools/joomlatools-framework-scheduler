<?php
/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @copyright   Copyright (C) 2015 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        http://github.com/nooku/nooku-activities for the canonical source repository
 */

/**
 * Tasks model
 *
 * @author Ercan Ozkaya <https://github.com/ercanozkaya>
 * @package Koowa\Component\Scheduler
 */
class ComSchedulerModelTasks extends KModelDatabase
{
    public function __construct(KObjectConfig $config)
    {
        parent::__construct($config);

        $this->getState()
            ->insert('due', 'boolean')
            ->insert('status', 'int')
            ->insert('queue', 'int');
    }

    protected function _buildQueryColumns(KDatabaseQueryInterface $query)
    {
        if (!$query->isCountQuery()) {
            $query->columns('tbl.completed_on = 0 OR (NOW() > DATE_ADD(`completed_on`, INTERVAL `frequency` MINUTE)) AS due');
        }
    }

    protected function _buildQueryWhere(KDatabaseQueryInterface $query)
    {
        $state = $this->getState();

        if ($state->due) {
            $query->where('(tbl.completed_on = 0 OR (NOW() > DATE_ADD(completed_on, INTERVAL frequency MINUTE)))');
        }

        if (is_numeric($state->status) || !empty($state->status))
        {
            $query->where('tbl.status IN :status')
                ->bind(array('status' => (array) $state->status));
        }

        if (is_numeric($state->queue) || !empty($state->queue))
        {
            $query->where('tbl.queue IN :queue')
                ->bind(array('queue' => (array) $state->queue));
        }
    }
}