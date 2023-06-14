<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Config\ConfigKeyCategory;

#[ConfigKeyCategory('Tracker')]
class Tracker_ReportDao extends DataAccessObject
{
    #[\Tuleap\Config\ConfigKey('Define the maximum number of artifacts a report can render')]
    #[\Tuleap\Config\ConfigKeyHelp(<<<EOT
    Tuleap tracker reports can reach a limit. This limit is not clear today but it's known to fail around 600'000
    artifact in single tracker.
    This configuration variable is introduced to let admin access their tracker despite the large amount of artifacts
    in order to let them better filter the content.
    The default is 0 and means 'no limit'
    EOT)]
    #[\Tuleap\Config\ConfigKeyInt(0)]
    public const MAX_ARTIFACTS_IN_REPORT = 'max_artifacts_in_report';

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report';
    }

    public function searchById($id, $user_id)
    {
        $id      = $this->da->escapeInt($id);
        $user_id = $this->da->escapeInt($user_id);
        $sql     = "SELECT *
                FROM $this->table_name
                WHERE id = $id
                  AND (user_id IS NULL
                      OR user_id = $user_id)";
        return $this->retrieve($sql);
    }

    public function searchByTrackerId($tracker_id, $user_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $user_stm   = " ";
        if ($user_id) {
            $user_stm = "user_id = " . $this->da->escapeInt($user_id) . " OR ";
        }

        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND ($user_stm user_id IS NULL)
                ORDER BY name";
        return $this->retrieve($sql);
    }

    public function searchDefaultByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND user_id IS NULL
                ORDER BY is_default DESC, name ASC
                LIMIT 1";
        return $this->retrieve($sql);
    }

    public function searchDefaultReportByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND is_default = 1";
        return $this->retrieve($sql);
    }

    public function searchByUserId($user_id)
    {
        $user_id = $user_id ? '= ' . $this->da->escapeInt($user_id) : 'IS NULL';

        $sql = "SELECT *
                FROM $this->table_name
                WHERE user_id $user_id
                ORDER BY name";
        return $this->retrieve($sql);
    }

    public function create(
        $name,
        $description,
        $current_renderer_id,
        $parent_report_id,
        $user_id,
        $is_default,
        $tracker_id,
        $is_query_displayed,
        $is_in_expert_mode,
        $expert_query,
    ) {
        $name                = $this->da->quoteSmart($name);
        $description         = $this->da->quoteSmart($description);
        $current_renderer_id = $this->da->escapeInt($current_renderer_id);
        $parent_report_id    = $this->da->escapeInt($parent_report_id);
        $user_id             = $user_id ? $this->da->escapeInt($user_id) : 'NULL';
        $is_default          = $this->da->escapeInt($is_default);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $is_query_displayed  = $this->da->escapeInt($is_query_displayed);
        $is_in_expert_mode   = $this->da->escapeInt($is_in_expert_mode);
        $expert_query        = $this->da->quoteSmart($expert_query);
        $sql                 = "INSERT INTO $this->table_name
                (name, description, current_renderer_id, parent_report_id, user_id, is_default, tracker_id, is_query_displayed, is_in_expert_mode, expert_query)
                VALUES ($name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed, $is_in_expert_mode, $expert_query)";
        return $this->updateAndGetLastId($sql);
    }

    public function save(
        $id,
        $name,
        $description,
        $current_renderer_id,
        $parent_report_id,
        $user_id,
        $is_default,
        $tracker_id,
        $is_query_displayed,
        $is_in_expert_mode,
        $expert_query,
        $updated_by_id,
    ) {
        $id                  = $this->da->escapeInt($id);
        $name                = $this->da->quoteSmart($name);
        $description         = $this->da->quoteSmart($description);
        $current_renderer_id = $this->da->escapeInt($current_renderer_id);
        $parent_report_id    = $parent_report_id ? $this->da->escapeInt($parent_report_id) : 'NULL';
        $user_id             = $user_id ? $this->da->escapeInt($user_id) : 'NULL';
        $is_default          = $this->da->escapeInt($is_default);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $is_query_displayed  = $this->da->escapeInt($is_query_displayed);
        $is_in_expert_mode   = $this->da->escapeInt($is_in_expert_mode);
        $expert_query        = $this->da->quoteSmart($expert_query);
        $updated_by_id       = $this->da->escapeInt($updated_by_id);
        $updated_at          = $_SERVER['REQUEST_TIME'];
        $sql                 = "UPDATE $this->table_name SET
                   name                = $name,
                   description         = $description,
                   current_renderer_id = $current_renderer_id,
                   parent_report_id    = $parent_report_id,
                   user_id             = $user_id,
                   is_default          = $is_default,
                   tracker_id          = $tracker_id,
                   is_query_displayed  = $is_query_displayed,
                   is_in_expert_mode   = $is_in_expert_mode,
                   expert_query        = $expert_query,
                   updated_by          = $updated_by_id,
                   updated_at          = $updated_at
                WHERE id = $id ";
        return $this->update($sql);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM $this->table_name WHERE id = " . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    public function duplicate($from_report_id, $to_tracker_id)
    {
        $from_report_id = $this->da->escapeInt($from_report_id);
        $to_tracker_id  = $this->da->escapeInt($to_tracker_id);
        $sql            = "INSERT INTO $this->table_name (project_id, user_id, tracker_id, is_default, name, description, current_renderer_id, parent_report_id, is_query_displayed, is_in_expert_mode, expert_query)
                SELECT project_id, user_id, $to_tracker_id, is_default, name, description, current_renderer_id, $from_report_id, is_query_displayed, is_in_expert_mode, expert_query
                FROM $this->table_name
                WHERE id = $from_report_id";
        return $this->updateAndGetLastId($sql);
    }
}
