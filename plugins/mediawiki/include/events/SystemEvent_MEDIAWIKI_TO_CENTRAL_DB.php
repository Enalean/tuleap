<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

namespace Tuleap\Mediawiki\Events;

use SystemEvent;
use Tuleap\Mediawiki\Migration\MoveToCentralDbDao;
use Exception;

// @codingStandardsIgnoreLine
class SystemEvent_MEDIAWIKI_TO_CENTRAL_DB extends SystemEvent
{
    public const NAME = 'MEDIAWIKI_TO_CENTRAL_DB';

    public const ALL = 'all';

    /** @var MoveToCentralDbDao */
    private $move_to_central_db;

    public function injectDependencies(MoveToCentralDbDao $move_to_central_db)
    {
        $this->move_to_central_db    = $move_to_central_db;
    }

    public function process()
    {
        if (! $this->move_to_central_db->testDatabaseAvailability()) {
            throw new Exception('No central database defined or central database not accessible by DB user');
        }
        if ($this->areAllProjectsMigrated()) {
            $this->move_to_central_db->moveAll();
        } else {
            $this->move_to_central_db->move($this->getProjectIdFromParameters());
        }
        $this->done();
    }

    private function areAllProjectsMigrated()
    {
        $parameters = $this->getParametersAsArray();
        return isset($parameters[0]) && $parameters[0] === self::ALL;
    }

    private function getProjectIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();
        return (int) $parameters[0];
    }

    public function verbalizeParameters($with_link)
    {
        if ($this->areAllProjectsMigrated()) {
            return "All projects";
        } else {
            return "Project: " . $this->getProjectIdFromParameters();
        }
    }
}
