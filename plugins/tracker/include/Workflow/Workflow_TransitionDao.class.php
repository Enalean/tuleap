<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Workflow_TransitionDao extends DataAccessObject
{
    public function addTransition($workflow_id, $from, $to): int
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $to   = $this->da->escapeInt($to);
        $from   = $this->da->escapeInt($from);
        $sql = "INSERT INTO tracker_workflow_transition (workflow_id, from_id, to_id)
                VALUES ($workflow_id, $from, $to)";
        return (int) $this->updateAndGetLastId($sql);
    }

    public function deleteTransition($workflow_id, $from, $to)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $from        = $this->da->escapeInt($from);
        $to          = $this->da->escapeInt($to);

        $sql = "DELETE tracker_workflow_transition, tracker_workflow_transition_condition_field_notempty,
                tracker_workflow_transition_condition_comment_notempty, tracker_workflow_transition_postactions_field_date,
                tracker_workflow_transition_postactions_field_int, tracker_workflow_transition_postactions_field_float,
                tracker_workflow_transition_postactions_cibuild, paff, paffv
            FROM tracker_workflow_transition
                 LEFT JOIN tracker_workflow_transition_condition_field_notempty
            ON tracker_workflow_transition_condition_field_notempty.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_condition_comment_notempty
            ON tracker_workflow_transition_condition_comment_notempty.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_field_date
            ON tracker_workflow_transition_postactions_field_date.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_field_int
            ON tracker_workflow_transition_postactions_field_int.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_field_float
            ON tracker_workflow_transition_postactions_field_float.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_cibuild
            ON tracker_workflow_transition_postactions_cibuild.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN (
                     plugin_tracker_workflow_postactions_frozen_fields AS paff
                        JOIN plugin_tracker_workflow_postactions_frozen_fields_value AS paffv
                     ON paff.id = paffv.postaction_id
                 )
            ON paff.transition_id = tracker_workflow_transition.transition_id
            WHERE tracker_workflow_transition.from_id = $from AND tracker_workflow_transition.to_id = $to AND
                tracker_workflow_transition.workflow_id = $workflow_id";
        return $this->update($sql);
    }

    public function deleteWorkflowTransitions($workflow_id)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);

        $sql = "DELETE tracker_workflow_transition, tracker_workflow_transition_condition_field_notempty,
                tracker_workflow_transition_condition_comment_notempty, tracker_workflow_transition_postactions_field_date,
                tracker_workflow_transition_postactions_field_int, tracker_workflow_transition_postactions_field_float,
                tracker_workflow_transition_postactions_cibuild, paro, parof
            FROM tracker_workflow_transition
                 LEFT JOIN tracker_workflow_transition_condition_field_notempty
            ON tracker_workflow_transition_condition_field_notempty.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_condition_comment_notempty
            ON tracker_workflow_transition_condition_comment_notempty.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_field_date
            ON tracker_workflow_transition_postactions_field_date.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_field_int
            ON tracker_workflow_transition_postactions_field_int.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_field_float
            ON tracker_workflow_transition_postactions_field_float.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN tracker_workflow_transition_postactions_cibuild
            ON tracker_workflow_transition_postactions_cibuild.transition_id = tracker_workflow_transition.transition_id
                 LEFT JOIN (
                     plugin_tracker_workflow_postactions_frozen_fields AS paro
                         JOIN plugin_tracker_workflow_postactions_frozen_fields_value AS parof
                     ON paro.id = parof.postaction_id
                 )
            ON paro.transition_id = tracker_workflow_transition.transition_id
            WHERE tracker_workflow_transition.workflow_id = $workflow_id";

        return $this->update($sql);
    }

    public function searchByWorkflow($workflow_id)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $sql = "SELECT * FROM tracker_workflow_transition
                WHERE workflow_id=$workflow_id";
        return $this->retrieve($sql);
    }

    public function searchByWorkflowAndToId(int $workflow_id, int $to_id)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $to_id       = $this->da->escapeInt($to_id);

        $sql = "SELECT *
                FROM tracker_workflow_transition
                WHERE workflow_id=$workflow_id
                AND to_id=$to_id";

        return $this->retrieve($sql);
    }

    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT T.*
                FROM tracker_workflow_transition AS T
                    INNER JOIN tracker_workflow AS W ON (
                        T.workflow_id = W.workflow_id
                        AND W.tracker_id = $tracker_id
                    )";

        return $this->retrieve($sql);
    }

    public function searchTransitionId($workflow_id, $from, $to)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);
        $from = $this->da->escapeInt($from);
        $to = $this->da->escapeInt($to);
        $sql = "SELECT * FROM tracker_workflow_transition
                WHERE workflow_id=$workflow_id
                AND from_id=$from
                AND to_id=$to";
        return $this->retrieve($sql);
    }

    public function searchById($transition_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);
        $sql = "SELECT * FROM tracker_workflow_transition
                WHERE transition_id=$transition_id";
        return $this->retrieve($sql);
    }
}
