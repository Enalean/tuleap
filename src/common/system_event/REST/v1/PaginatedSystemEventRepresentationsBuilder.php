<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\SystemEvent\REST\v1;

use SystemEventDao;
use SystemEventManager;

class PaginatedSystemEventRepresentationsBuilder
{

    public function __construct(SystemEventDao $dao, SystemEventManager $manager)
    {
        $this->dao     = $dao;
        $this->manager = $manager;
    }

    /**
     * @return PaginatedSystemEventRepresentations
     */
    public function getAllMatchingEvents($status, $limit, $offset)
    {
        $event_representations = [];
        $event_rows            = $this->dao->searchAllMatchingEvents($status, $limit, $offset);
        $total_rows            = (int) $this->dao->foundRows();

        foreach ($event_rows as $event_row) {
            $event = $this->manager->getInstanceFromRow($event_row);
            $representation = new SystemEventRepresentation();
            $representation->build($event);

            $event_representations[] = $representation;
        }

        return new PaginatedSystemEventRepresentations($event_representations, $total_rows);
    }
}
