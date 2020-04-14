<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Taskboard\Column;

use Cardwall_Column;
use Cardwall_OnTop_ColumnDao;
use Tracker;
use TrackerFactory;

class MilestoneTrackerRetriever
{
    /** @var Cardwall_OnTop_ColumnDao */
    private $dao;
    /** @var TrackerFactory */
    private $tracker_factory;

    public function __construct(Cardwall_OnTop_ColumnDao $dao, TrackerFactory $tracker_factory)
    {
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @throws InvalidColumnException
     */
    public function getMilestoneTrackerOfColumn(Cardwall_Column $column): Tracker
    {
        $dar = $this->dao->searchByColumnId($column->getId());
        if ($dar === false) {
            throw new InvalidColumnException();
        }
        $row = $dar->getRow();
        $tracker = $this->tracker_factory->getTrackerById($row['tracker_id']);
        if ($tracker === null) {
            throw new \RuntimeException('Tracker does not exist');
        }
        return $tracker;
    }
}
