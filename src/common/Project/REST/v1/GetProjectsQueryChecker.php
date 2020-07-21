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
 *
 */

namespace Tuleap\Project\REST\v1;

use EventManager;
use Luracast\Restler\RestException;
use Tuleap\Project\ProjectStatusMapper;
use Tuleap\REST\Event\GetAdditionalCriteria;

class GetProjectsQueryChecker
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
     * @param array $json_query
     * @param bool  $is_user_a_rest_project_manager
     * @throws RestException
     */
    public function checkQuery(array $json_query, $is_user_a_rest_project_manager)
    {
        $this->checkExternalCriteria($json_query);

        if (isset($json_query['is_member_of']) && ! $json_query['is_member_of']) {
            throw new RestException(400, "Searching for projects you are not member of is not supported. Use 'is_member_of': true");
        }

        if (isset($json_query['is_tracker_admin']) && ! $json_query['is_tracker_admin']) {
            throw new RestException(
                400,
                "Searching for projects you are not administrator of at least one tracker is not supported. Use 'is_tracker_admin': true"
            );
        }

        if (isset($json_query['with_status'])) {
            $with_status = $json_query['with_status'];
            if (! $is_user_a_rest_project_manager) {
                throw new RestException(
                    403,
                    "You don't have enough rights to perform a query using 'with_status'"
                );
            }

            if ((! $with_status || ! ProjectStatusMapper::isValidProjectStatusLabel($with_status))) {
                throw new RestException(
                    400,
                    "Please provide a valid status: 'active', 'pending', 'suspended', 'deleted'"
                );
            }
        }
    }

    /**
     * @param array $json_query
     * @throws RestException
     */
    private function checkExternalCriteria(array $json_query)
    {
        $is_query_valid      = isset($json_query['shortname']) || isset($json_query['is_member_of']) || isset($json_query['is_admin_of']) || isset($json_query['is_tracker_admin']) || isset($json_query['with_status']);
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
                "You can only search on 'shortname', 'is_member_of': true, 'is_admin_of': true,  'is_tracker_admin': true, 'with_status'" . $possible_criteria .
                ". If the query is valid, your plugin is maybe not installed."
            );
        }
    }
}
