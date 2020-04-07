<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
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
    public const MOVE_PROJECT_NAME                  = 'move-artifact';
    public const DELETE_PROJECT_NAME                = 'test-delete-artifacts';
    public const TRACKER_FIELDS_PROJECT_NAME        = 'test-tracker-fields';
    public const TRACKER_ADMINISTRATOR_PROJECT_NAME = 'test-tracker-project-filter';
    public const TRACKER_WORKFLOWS_PROJECT_NAME     = 'test-tracker-workflows';
    public const TRACKER_SEMANTICS_PROJECT_NAME     = 'test-tracker-semantics';
    private const TRACKER_FILE_URL_PROJECT_NAME     = 'test-tracker-file-url';
    private const REST_XML_API_PROJECT_NAME         = 'rest-xml-api';
    private const COMPUTED_VALUE_PROJECT_NAME       = 'computed-fields-default-value';

    public const MOVE_TRACKER_SHORTNAME                           = 'ToMoveArtifacts';
    public const BASE_TRACKER_SHORTNAME                           = 'base';
    public const DELETE_TRACKER_SHORTNAME                         = 'diasabled_delete_artifacts_testing_2';
    public const TRACKER_FIELDS_TRACKER_SHORTNAME                 = 'tracker_fields_tracker';
    public const SIMPLE_01_TRACKER_SHORTNAME                      = 'simple_tracker_01';
    public const SIMPLE_02_TRACKER_SHORTNAME                      = 'simple_tracker_02';
    public const TRACKER_WITH_WORKFLOWS_SHORTNAME                 = 'workflows_tracker';
    public const TRACKER_WORKFLOW_WITH_TRANSITIONS_SHORTNAME      = 'workflows_tracker_transitions';
    public const TRACKER_WORKFLOW_SIMPLE_MODE_SHORTNAME           = 'workflow_simple_mode';
    public const TRACKER_WORKFLOW_SIMPLE_MODE_TO_SWITCH_SHORTNAME = 'simple_workflow_to_switch';
    public const TRACKER_WORKFLOW_SIMPLE_MODE_FROM_XML_SHORTNAME  = 'workflow_simple_mode_from_xml';
    public const TRACKER_WITH_TIMEFRAME_SEMANTIC_SHORTNAME        = 'tracker_semantic_timeframe';
    private const TRACKER_FILE_URL_SHORTNAME                      = 'image';
    private const REST_XML_API_TRACKER_SHORTNAME                  = 'epic';
    private const COMPUTED_VALUE_TRACKER_SHORTNAME                = 'ComputedFieldsDefaultValues';

    protected $tracker_administrator_project_id;
    protected $tracker_workflows_project_id;
    protected $rest_xml_api_project_id;

    protected $delete_tracker_id;
    protected $move_tracker_id;
    protected $base_tracker_id;
    protected $tracker_fields_tracker_id;
    protected $tracker_workflows_tracker_id;
    protected $simple_mode_workflow_tracker_id;
    protected $simple_mode_workflow_to_switch_tracker_id;
    protected $simple_mode_from_xml_tracker_id;
    protected $tracker_file_url_id;
    protected $rest_xml_api_tracker_id;
    protected $tracker_workflow_transitions_tracker_id;
    protected $computed_value_tracker_id;

    protected $base_artifact_ids   = [];
    protected $delete_artifact_ids = [];

    /**
     * @var int
     */
    public $tracker_with_timeframe_semantic_id;

    public function setUp(): void
    {
        parent::setUp();

        $move_project_id                        = $this->getProjectId(self::MOVE_PROJECT_NAME);
        $delete_project_id                      = $this->getProjectId(self::DELETE_PROJECT_NAME);
        $tracker_fields_project_id              = $this->getProjectId(self::TRACKER_FIELDS_PROJECT_NAME);
        $this->tracker_administrator_project_id = $this->getProjectId(self::TRACKER_ADMINISTRATOR_PROJECT_NAME);
        $this->tracker_workflows_project_id     = $this->getProjectId(self::TRACKER_WORKFLOWS_PROJECT_NAME);
        $file_url_project_id                    = $this->getProjectId(self::TRACKER_FILE_URL_PROJECT_NAME);
        $tracker_semantics_project_id           = $this->getProjectId(self::TRACKER_SEMANTICS_PROJECT_NAME);
        $this->rest_xml_api_project_id          = $this->getProjectId(self::REST_XML_API_PROJECT_NAME);
        $computed_value_project_id              = $this->getProjectId(self::COMPUTED_VALUE_PROJECT_NAME);

        $this->move_tracker_id                           = $this->tracker_ids[$move_project_id][self::MOVE_TRACKER_SHORTNAME];
        $this->base_tracker_id                           = $this->tracker_ids[$move_project_id][self::BASE_TRACKER_SHORTNAME];
        $this->delete_tracker_id                         = $this->tracker_ids[$delete_project_id][self::DELETE_TRACKER_SHORTNAME];
        $this->tracker_fields_tracker_id                 = $this->tracker_ids[$tracker_fields_project_id][self::TRACKER_FIELDS_TRACKER_SHORTNAME];
        $this->tracker_workflows_tracker_id              = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WITH_WORKFLOWS_SHORTNAME];
        $this->tracker_workflow_transitions_tracker_id   = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WORKFLOW_WITH_TRANSITIONS_SHORTNAME];
        $this->simple_mode_workflow_tracker_id           = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WORKFLOW_SIMPLE_MODE_SHORTNAME];
        $this->simple_mode_workflow_to_switch_tracker_id = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WORKFLOW_SIMPLE_MODE_TO_SWITCH_SHORTNAME];
        $this->simple_mode_from_xml_tracker_id           = $this->tracker_ids[$this->tracker_workflows_project_id][self::TRACKER_WORKFLOW_SIMPLE_MODE_FROM_XML_SHORTNAME];
        $this->tracker_file_url_id                       = $this->tracker_ids[$file_url_project_id][self::TRACKER_FILE_URL_SHORTNAME];
        $this->tracker_with_timeframe_semantic_id        = $this->tracker_ids[$tracker_semantics_project_id][self::TRACKER_WITH_TIMEFRAME_SEMANTIC_SHORTNAME];
        $this->rest_xml_api_tracker_id                   = $this->tracker_ids[$this->rest_xml_api_project_id][self::REST_XML_API_TRACKER_SHORTNAME];
        $this->computed_value_tracker_id                 = $this->tracker_ids[$computed_value_project_id][self::COMPUTED_VALUE_TRACKER_SHORTNAME];

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
        $tracker = $this->tracker_representations[$tracker_id];

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

    protected function getAUsedField(int $tracker_id, string $field_shortname): ?array
    {
        $tracker = $this->tracker_representations[$tracker_id];
        foreach ($tracker['fields'] as $tracker_field) {
            if ($tracker_field['name'] === $field_shortname) {
                return $tracker_field;
            }
        }

        $this->fail();

        return null;
    }

    protected function getAUsedFieldId(int $tracker_id, string $field_shortname): ?int
    {
        $field = $this->getAUsedField($tracker_id, $field_shortname);
        if ($field !== null) {
            return $field['field_id'];
        }

        $this->fail();

        return null;
    }
}
