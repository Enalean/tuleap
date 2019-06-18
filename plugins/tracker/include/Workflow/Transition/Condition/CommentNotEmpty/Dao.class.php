<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

class Workflow_Transition_Condition_CommentNotEmpty_Dao extends \Tuleap\DB\DataAccessObject // phpcs:ignore
{

    public function create($transition_id, $is_comment_required)
    {
        $is_comment_required = $is_comment_required ? 1 : 0;

        $sql = "REPLACE INTO tracker_workflow_transition_condition_comment_notempty(transition_id, is_comment_required)
                VALUES (?, ?)";

        $this->getDB()->run($sql, $transition_id, $is_comment_required);
    }

    public function searchByTransitionId($transition_id)
    {
        return $this->getDB()->row(
            'SELECT * FROM tracker_workflow_transition_condition_comment_notempty WHERE transition_id = ?',
            $transition_id
        );
    }

    public function duplicate($from_transition_id, $to_transition_id)
    {
        $sql = "INSERT INTO tracker_workflow_transition_condition_comment_notempty (transition_id, is_comment_required)
                SELECT ?, is_comment_required
                FROM tracker_workflow_transition_condition_comment_notempty
                WHERE transition_id = ?";

        $this->getDB()->run($sql, $to_transition_id, $from_transition_id);
    }
}
