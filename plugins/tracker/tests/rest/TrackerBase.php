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

namespace Tuleap\Tracker\Tests\REST;

use RestBase;
use REST_TestDataBuilder;

class TrackerBase extends RestBase
{
    const MOVE_PROJECT_NAME                  = 'move-artifact';
    const DELETE_PROJECT_NAME                = 'test-delete-artifacts';
    const TRACKER_FIELDS_PROJECT_NAME        = 'test-tracker-fields';
    const TRACKER_ADMINISTRATOR_PROJECT_NAME = 'test-tracker-project-filter';
    const TRACKER_WORKFLOWS_PROJECT_NAME     = 'test-tracker-workflows';

    const MOVE_TRACKER_SHORTNAME                      = 'ToMoveArtifacts';
    const BASE_TRACKER_SHORTNAME                      = 'base';
    const DELETE_TRACKER_SHORTNAME                    = 'diasabled_delete_artifacts_testing_2';
    const TRACKER_FIELDS_TRACKER_SHORTNAME            = 'tracker_fields_tracker';
    const SIMPLE_01_TRACKER_SHORTNAME                 = 'simple_tracker_01';
    const SIMPLE_02_TRACKER_SHORTNAME                 = 'simple_tracker_02';
    const TRACKER_WITH_WORKFLOWS_SHORTNAME            = 'workflows_tracker';
    const TRACKER_WORKFLOW_WITH_TRANSITIONS_SHORTNAME = 'workflows_tracker_transitions';

    protected $tracker_administrator_project_id;

    protected $delete_tracker_id;
    protected $move_tracker_id;
    protected $base_tracker_id;
    protected $tracker_fields_tracker_id;
    protected $tracker_workflows_tracker_id;

    protected $base_artifact_ids   = [];
    protected $delete_artifact_ids = [];

    public function setUp()
    {
        parent::setUp();

        $move_project_id                        = $this->getProjectId(self::MOVE_PROJECT_NAME);
        $delete_project_id                      = $this->getProjectId(self::DELETE_PROJECT_NAME);
        $tracker_fields_project_id              = $this->getProjectId(self::TRACKER_FIELDS_PROJECT_NAME);
        $this->tracker_administrator_project_id = $this->getProjectId(self::TRACKER_ADMINISTRATOR_PROJECT_NAME);
        $tracker_workflows_project_id           = $this->getProjectId(self::TRACKER_WORKFLOWS_PROJECT_NAME);

        $this->move_tracker_id                         = $this->tracker_ids[$move_project_id][self::MOVE_TRACKER_SHORTNAME];
        $this->base_tracker_id                         = $this->tracker_ids[$move_project_id][self::BASE_TRACKER_SHORTNAME];
        $this->delete_tracker_id                       = $this->tracker_ids[$delete_project_id][self::DELETE_TRACKER_SHORTNAME];
        $this->tracker_fields_tracker_id               = $this->tracker_ids[$tracker_fields_project_id][self::TRACKER_FIELDS_TRACKER_SHORTNAME];
        $this->tracker_workflows_tracker_id            = $this->tracker_ids[$tracker_workflows_project_id][self::TRACKER_WITH_WORKFLOWS_SHORTNAME];
        $this->tracker_workflow_transitions_tracker_id = $this->tracker_ids[$tracker_workflows_project_id][self::TRACKER_WORKFLOW_WITH_TRANSITIONS_SHORTNAME];

        $this->getBaseArtifactIds();
        $this->getDeleteArtifactIds();
    }

    private function getBaseArtifactIds()
    {
        $this->getArtifactIds(
            $this->base_tracker_id,
            $this->base_artifact_ids
        );
    }

    private function getDeleteArtifactIds()
    {
        $this->getArtifactIds(
            $this->delete_tracker_id,
            $this->delete_artifact_ids
        );
    }

    /**
     * Returns all transitions combinations:
     * - current transitions (already used)
     * - available transitions to create (not used yet)
     *
     * @param $tracker_id
     *
     * @return array
     */
    protected function getAllTransitionCombinations($tracker_id)
    {
        $tracker = $this->getResponseByName(
            REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->setup_client->get("trackers/$tracker_id")
        )->json();

        $tracker_workflow_field_key = array_search(
            $tracker["workflow"]["field_id"],
            array_column(
                $tracker["fields"],
                "field_id"
            )
        );
        $all_field_values_ids = array_column($tracker["fields"][$tracker_workflow_field_key]["values"], "id");

        $all_transitions = [
            "transitions" => [],
            "missing_transitions" => []
        ];

        foreach ($tracker["workflow"]["transitions"] as $transition) {
            $all_transitions["transitions"][] = [
                "from_id" => $transition["from_id"],
                "to_id" => $transition["to_id"]
            ];
        }

        foreach (array_merge([null], $all_field_values_ids) as $from_id) {
            foreach ($all_field_values_ids as $to_id) {
                if ($from_id !== $to_id
                    && !in_array(["from_id" => $from_id, "to_id" => $to_id], $all_transitions["transitions"])) {
                    $all_transitions["missing_transitions"][] = [
                        "from_id" => $from_id,
                        "to_id" => $to_id
                    ];
                }
            }
        };

        return $all_transitions;
    }
}
