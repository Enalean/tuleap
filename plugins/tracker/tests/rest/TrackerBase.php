<?php
/**
 * Copyright Enalean (c) 2018 - 2019. All rights reserved.
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

class TrackerBase extends RestBase
{
    const MOVE_PROJECT_NAME                  = 'move-artifact';
    const DELETE_PROJECT_NAME                = 'test-delete-artifacts';
    const TRACKER_FIELDS_PROJECT_NAME        = 'test-tracker-fields';
    const TRACKER_ADMINISTRATOR_PROJECT_NAME = 'test-tracker-project-filter';
    const TRACKER_WORKFLOWS_PROJECT_NAME     = 'test-tracker-workflows';

    const MOVE_TRACKER_SHORTNAME                           = 'ToMoveArtifacts';
    const BASE_TRACKER_SHORTNAME                           = 'base';
    const DELETE_TRACKER_SHORTNAME                         = 'diasabled_delete_artifacts_testing_2';
    const TRACKER_FIELDS_TRACKER_SHORTNAME                 = 'tracker_fields_tracker';
    const SIMPLE_01_TRACKER_SHORTNAME                      = 'simple_tracker_01';
    const SIMPLE_02_TRACKER_SHORTNAME                      = 'simple_tracker_02';
    const TRACKER_WITH_WORKFLOWS_SHORTNAME                 = 'workflows_tracker';
    const TRACKER_WORKFLOW_WITH_TRANSITIONS_SHORTNAME      = 'workflows_tracker_transitions';
    const TRACKER_WORKFLOW_SIMPLE_MODE_SHORTNAME           = 'workflow_simple_mode';
    const TRACKER_WORKFLOW_SIMPLE_MODE_TO_SWITCH_SHORTNAME = 'simple_workflow_to_switch';

    protected $tracker_administrator_project_id;
    protected $tracker_workflows_project_id;

    protected $delete_tracker_id;
    protected $move_tracker_id;
    protected $base_tracker_id;
    protected $tracker_fields_tracker_id;
    protected $tracker_workflows_tracker_id;
    protected $simple_mode_workflow_tracker_id;
    protected $simple_mode_workflow_to_switch_tracker_id;

    protected $base_artifact_ids   = [];
    protected $delete_artifact_ids = [];

    public function setUp() : void
    {
        parent::setUp();

        $move_project_id                        = $this->getProjectId(self::MOVE_PROJECT_NAME);
        $delete_project_id                      = $this->getProjectId(self::DELETE_PROJECT_NAME);
        $tracker_fields_project_id              = $this->getProjectId(self::TRACKER_FIELDS_PROJECT_NAME);
        $this->tracker_administrator_project_id = $this->getProjectId(self::TRACKER_ADMINISTRATOR_PROJECT_NAME);
        $this->tracker_workflows_project_id     = $this->getProjectId(self::TRACKER_WORKFLOWS_PROJECT_NAME);

        $this->move_tracker_id                           = $this->tracker_ids[$move_project_id][self::MOVE_TRACKER_SHORTNAME];
        $this->base_tracker_id                           = $this->tracker_ids[$move_project_id][self::BASE_TRACKER_SHORTNAME];
        $this->delete_tracker_id                         = $this->tracker_ids[$delete_project_id][self::DELETE_TRACKER_SHORTNAME];
        $this->tracker_fields_tracker_id                 = $this->tracker_ids[$tracker_fields_project_id][self::TRACKER_FIELDS_TRACKER_SHORTNAME];
        $this->tracker_workflows_tracker_id              = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WITH_WORKFLOWS_SHORTNAME];
        $this->tracker_workflow_transitions_tracker_id   = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WORKFLOW_WITH_TRANSITIONS_SHORTNAME];
        $this->simple_mode_workflow_tracker_id           = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WORKFLOW_SIMPLE_MODE_SHORTNAME];
        $this->simple_mode_workflow_to_switch_tracker_id = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WORKFLOW_SIMPLE_MODE_TO_SWITCH_SHORTNAME];

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

    protected function getSpecificTransition(
        int $tracker_id,
        string $workflow_field_shortname,
        string $from_label,
        string $to_label
    ): array {
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->setup_client->get("trackers/$tracker_id")
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker = $response->json();

        $status_field_id = 0;
        $from_value_id   = 0;
        $to_value_id     = 0;

        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] === $workflow_field_shortname) {
                $status_field_id = $tracker_field['field_id'];

                foreach ($tracker_field['values'] as $field_value) {
                    if ($field_value['label'] === $from_label) {
                        $from_value_id = $field_value['id'];
                    }

                    if ($field_value['label'] === $to_label) {
                        $to_value_id = $field_value['id'];
                    }
                }
                break;
            }
        }

        if ($status_field_id === 0 || $from_value_id === 0 || $to_value_id === 0) {
            $this->fail();
        }

        $found_transition = null;
        foreach ($tracker["workflow"]["transitions"] as $transition) {
            if ($transition['from_id'] === $from_value_id && $transition['to_id'] === $to_value_id) {
                $found_transition = $transition;
                break;
            }
        }

        if ($found_transition === null) {
            $this->fail();
        }

        return $found_transition;
    }

    protected function getAUsedField(int $tracker_id, string $workflow_field_shortname) : int
    {
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->setup_client->get("trackers/$tracker_id")
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $tracker = $response->json();

        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] !== $workflow_field_shortname) {
                return $tracker_field['field_id'];
            }
        }

        $this->fail();
    }
}
