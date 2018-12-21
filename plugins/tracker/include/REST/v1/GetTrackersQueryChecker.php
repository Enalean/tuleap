<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Tracker\REST\v1;

use EventManager;
use Luracast\Restler\RestException;
use Tuleap\REST\Event\GetAdditionalCriteria;

class GetTrackersQueryChecker
{
    /**
     * EventManager
     */
    private $event_manager;

    /**
     * @param $event_manager
     */
    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    /**
     * @param $json_query
     * @throws RestException
     */
    public function checkQuery($json_query)
    {
        $this->checkExternalCriteria($json_query);

        if (isset($json_query['is_tracker_admin']) && ! $json_query['is_tracker_admin']) {
            throw new RestException(
                400,
                "Filtering for trackers you are not administrator is not supported. Use 'is_tracker_admin': true"
            );
        }
    }

    /**
     * @param array $json_query
     * @throws RestException
     */
    private function checkExternalCriteria(array $json_query)
    {
        $is_query_valid      = isset($json_query['is_tracker_admin']);
        $additional_criteria = new GetAdditionalCriteria();
        $this->event_manager->processEvent($additional_criteria);

        foreach ($additional_criteria->getCriteria() as $criterion => $expected_value) {
            if (isset($json_query[$criterion])) {
                $is_query_valid = true;
            }
        }

        if (! $is_query_valid) {
            $possible_criteria = implode(", ", $additional_criteria->getCriteria());
            if (! empty($possible_criteria)) {
                $possible_criteria = ", " . $possible_criteria;
            }
            throw new RestException(
                400,
                "You can only search on 'is_tracker_admin': true" . $possible_criteria . ". If the query is valid, your plugin is maybe not installed."
            );
        }
    }
}
