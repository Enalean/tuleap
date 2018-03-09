<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

use PFUser;
use Tuleap\Timetracking\Time\TimeRetriever;

class TimetrackingRepresentationBuilder
{
    /**
     * @var TimeRetriever
     */
    private $time_retriever;

    public function __construct(TimeRetriever $time_retriever)
    {
        $this->time_retriever = $time_retriever;
    }

    /**
     * @return TimetrackingRepresentation[]
     */
    public function buildAllRepresentationsForUser(PFUser $user)
    {
        $representations = [];

        foreach ($this->time_retriever->getAllTimesForUser($user) as $time) {
            $representation = new TimetrackingRepresentation();
            $representation->build($time);

            $representations[] = $representation;
        }

        return $representations;
    }
}
