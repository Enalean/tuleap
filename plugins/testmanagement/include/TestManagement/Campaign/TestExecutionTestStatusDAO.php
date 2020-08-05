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

namespace Tuleap\TestManagement\Campaign;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class TestExecutionTestStatusDAO extends DataAccessObject
{
    /**
     * @return int[]
     * @psalm-return array{notrun: int, blocked: int, passed: int, failed: int}
     */
    public function searchTestStatusesInACampaign(InformationNeededToRetrieveTestStatusOfACampaign $information): array
    {
        $current_user_ugroup_ids_in_filter = EasyStatement::open()->in('?*', $information->current_user_ugroup_ids);

        $sql = <<<EOF
            SELECT test_status, SUM(nb) AS nb
            FROM (
                SELECT tracker_field_list_bind_static_value.label AS test_status, COUNT(test_exec.id) AS nb
                FROM tracker_artifact AS test_campaign
                JOIN tracker_changeset_value AS campaign_cv ON (campaign_cv.changeset_id = test_campaign.last_changeset_id AND campaign_cv.field_id = ?)
                JOIN tracker_changeset_value_artifactlink AS campaign_artlink ON (campaign_artlink.changeset_value_id = campaign_cv.id)
                JOIN tracker_artifact AS test_exec ON (test_exec.id = campaign_artlink.artifact_id)
                JOIN tracker AS tracker_campaign ON (tracker_campaign.id = test_campaign.tracker_id)
                JOIN plugin_testmanagement AS testmanagement_config ON (testmanagement_config.project_id = tracker_campaign.group_id)
                LEFT JOIN permissions ON (permissions.object_id = CAST(test_exec.id AS CHAR CHARACTER SET utf8) AND permissions.permission_type = 'PLUGIN_TRACKER_ARTIFACT_ACCESS')
                JOIN tracker_field_list_bind_static_value
                JOIN tracker_changeset_value_list ON (tracker_changeset_value_list.bindvalue_id = tracker_field_list_bind_static_value.id)
                JOIN tracker_changeset_value ON (tracker_changeset_value_list.changeset_value_id = tracker_changeset_value.id AND tracker_changeset_value.changeset_id = test_exec.last_changeset_id AND tracker_changeset_value.field_id = ?)
                WHERE testmanagement_config.test_execution_tracker_id = test_exec.tracker_id AND (test_exec.use_artifact_permissions = 0 OR permissions.ugroup_id IN ($current_user_ugroup_ids_in_filter))
                    AND test_campaign.id = ? AND tracker_field_list_bind_static_value.label IN ('notrun', 'passed', 'failed', 'blocked')
                GROUP BY test_status
                UNION ALL
                SELECT 'notrun' AS test_status, 0 AS nb
                UNION ALL
                SELECT 'passed' AS test_status, 0 AS nb
                UNION ALL
                SELECT 'failed' AS test_status, 0 AS nb
                UNION ALL
                SELECT 'blocked' AS test_status, 0 AS nb
            ) AS sum_with_default_values
            GROUP BY test_status
            EOF;

        return $this->getDB()->safeQuery(
            $sql,
            array_merge(
                [
                    $information->test_campaign_art_link_field_id,
                    $information->test_exec_status_field_id,
                ],
                $current_user_ugroup_ids_in_filter->values(),
                [
                    $information->campaign_id,
                ]
            ),
            \PDO::FETCH_KEY_PAIR
        );
    }
}
