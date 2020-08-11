<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestPlan\TestDefinition;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class TestPlanTestDefinitionsTestStatusDAO extends DataAccessObject
{
    /**
     * @psalm-return array<int,array{test_status: "notrun"|"passed"|"failed"|"blocked", test_exec_id: int, test_exec_submitted_by: int, test_exec_submitted_on: int, test_campaign_id: int}>
     */
    public function searchTestStatusPerTestDefinitionInAMilestone(TestPlanMilestoneInformationNeededToRetrieveTestStatusPerTestDefinition $information): array
    {
        $test_definition_ids_in_filter     = EasyStatement::open()->in('?*', $information->test_definition_ids);
        $current_user_ugroup_ids_in_filter = EasyStatement::open()->in('?*', $information->current_user_ugroup_ids);

        $sql_test_exec_test_def = <<<EOF
        SELECT test_def.id AS test_def_id, test_exec_changeset.id AS test_exec_changeset_id, test_campaign_id # All test exec changesets for each test def intersected with all tests exec of the campaigns of a milestone
        FROM tracker_changeset_value_artifactlink AS test_exec_artlink
        JOIN tracker_changeset_value AS test_exec_cv ON (test_exec_artlink.changeset_value_id = test_exec_cv.id)
        JOIN tracker_changeset AS test_exec_changeset ON (test_exec_cv.changeset_id = test_exec_changeset.id)
        JOIN tracker_artifact AS test_exec ON (test_exec.id = test_exec_changeset.artifact_id)
        JOIN tracker_artifact AS test_def ON (test_exec_artlink.artifact_id = test_def.id)
        JOIN tracker AS test_def_tracker ON (test_def_tracker.id = test_def.tracker_id)
        JOIN plugin_testmanagement ON (plugin_testmanagement.project_id = test_def_tracker.group_id)
        JOIN (
            # All test execs of the campaigns of a milestone a user can see according to perms on artifact
            SELECT test_exec.id, test_campaign.id AS test_campaign_id
            FROM tracker_artifact AS test_campaign
            JOIN tracker_changeset_value AS campaign_cv ON (campaign_cv.changeset_id = test_campaign.last_changeset_id AND campaign_cv.field_id = ?)
            JOIN tracker_changeset_value_artifactlink AS campaign_artlink ON (campaign_artlink.changeset_value_id = campaign_cv.id)
            JOIN tracker_artifact AS test_exec ON (test_exec.id = campaign_artlink.artifact_id)
            JOIN tracker_changeset_value AS milestone_cv ON (milestone_cv.changeset_id = test_campaign.last_changeset_id AND milestone_cv.field_id = ?)
            JOIN tracker_changeset_value_artifactlink AS milestone_artlink ON (milestone_artlink.changeset_value_id = milestone_cv.id)
            JOIN tracker_artifact AS milestone ON (milestone.id = milestone_artlink.artifact_id)
            JOIN tracker AS tracker_milestone ON (tracker_milestone.id = milestone.tracker_id)
            JOIN plugin_testmanagement AS testmanagement_config ON (testmanagement_config.project_id = tracker_milestone.group_id)
            LEFT JOIN permissions ON (permissions.object_id = CAST(test_exec.id AS CHAR CHARACTER SET utf8) AND permissions.permission_type = 'PLUGIN_TRACKER_ARTIFACT_ACCESS')
            WHERE testmanagement_config.campaign_tracker_id = test_campaign.tracker_id AND testmanagement_config.test_execution_tracker_id = test_exec.tracker_id AND milestone.id = ? AND (test_exec.use_artifact_permissions = 0 OR permissions.ugroup_id IN ($current_user_ugroup_ids_in_filter))
        ) AS test_exec_in_milestone ON (test_exec_in_milestone.id = test_exec.id)
        WHERE test_exec.tracker_id = plugin_testmanagement.test_execution_tracker_id AND test_exec_artlink.artifact_id IN ($test_definition_ids_in_filter) AND test_exec_cv.field_id = ?
        EOF;

        $sql = <<<EOF
        SELECT test_exec_changeset_per_test_def.test_def_id, tracker_field_list_bind_static_value.label AS test_status, test_exec_changeset.artifact_id AS test_exec_id, test_exec_changeset.submitted_by AS test_exec_submitted_by, test_exec_changeset.submitted_on AS test_exec_submitted_on,  test_campaign_id
        FROM tracker_field_list_bind_static_value
        JOIN tracker_changeset_value_list ON (tracker_changeset_value_list.bindvalue_id = tracker_field_list_bind_static_value.id)
        JOIN tracker_changeset_value ON (tracker_changeset_value_list.changeset_value_id = tracker_changeset_value.id AND tracker_changeset_value.field_id = ?)
        JOIN (
            # As MySQL 5.7 does not support windowing function (i.e. ROW_NUMBER() OVER (PARTITION BY ... ORDER BY ...) we are forced
            # to identify the most recent changeset ID in two passes over the dataset with an uncorrelated subquery.
            SELECT all_test_execs_per_test_def.test_def_id, all_test_execs_per_test_def.test_exec_changeset_id, all_test_execs_per_test_def.test_campaign_id
            FROM ($sql_test_exec_test_def) AS all_test_execs_per_test_def
            JOIN (
                SELECT all_test_execs_per_test_def_2.test_def_id, MAX(all_test_execs_per_test_def_2.test_exec_changeset_id) AS test_exec_changeset_id
                FROM ($sql_test_exec_test_def) AS all_test_execs_per_test_def_2
                GROUP BY all_test_execs_per_test_def_2.test_def_id
            ) AS most_recent_test_exec_changeset_id_per_test_def ON (most_recent_test_exec_changeset_id_per_test_def.test_def_id = all_test_execs_per_test_def.test_def_id AND most_recent_test_exec_changeset_id_per_test_def.test_exec_changeset_id = all_test_execs_per_test_def.test_exec_changeset_id)
        ) AS test_exec_changeset_per_test_def ON (test_exec_changeset_per_test_def.test_exec_changeset_id = tracker_changeset_value.changeset_id)
        JOIN tracker_changeset AS test_exec_changeset ON (test_exec_changeset.id = tracker_changeset_value.changeset_id)
        WHERE tracker_field_list_bind_static_value.label IN ('notrun', 'passed', 'failed', 'blocked')
        EOF;

        $parameters_for_finding_all_test_exec_per_test_def = array_merge(
            [
                $information->test_campaign_art_link_field_id,
                $information->test_campaign_art_link_field_id,
                $information->milestone_id
            ],
            $current_user_ugroup_ids_in_filter->values(),
            $test_definition_ids_in_filter->values(),
            [$information->test_exec_art_link_field_id]
        );

        return $this->getDB()->safeQuery(
            $sql,
            array_merge(
                [
                    $information->test_exec_status_field_id,
                ],
                $parameters_for_finding_all_test_exec_per_test_def,
                $parameters_for_finding_all_test_exec_per_test_def,
            ),
            \PDO::FETCH_UNIQUE
        );
    }
}
