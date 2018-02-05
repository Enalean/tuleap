<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\AdditionalCriteria;

use Tuleap\DB\DataAccessObject;

class CommentDao extends DataAccessObject
{
    public function save($report_id, $comment)
    {
        $sql = "REPLACE INTO tracker_report_criteria_comment_value (report_id, comment)
                VALUES (?, ?)";

        $this->getDB()->run($sql, $report_id, $comment);
    }

    public function delete($report_id)
    {
        $sql = "DELETE FROM tracker_report_criteria_comment_value
                WHERE report_id = ?";

        $this->getDB()->run($sql, $report_id);
    }

    public function searchByReportId($report_id)
    {
        $sql = "SELECT comment
                FROM tracker_report_criteria_comment_value
                WHERE report_id = ?";

        return $this->getDB()->row($sql, $report_id);
    }
}
