<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Base class for field post action DAOs.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Transition_PostAction_FieldDao extends DataAccessObject
{

    /**
     * @var string The table column where the post-transition value is stored.
     */
    protected $value_column_name = 'value';

    /**
     * Escapes the post-transition value according to its type.
     */
    abstract protected function escapeValue($value);

    /**
     * Create a new postaction entry
     *
     * @param int $transition_id The transition the post action belongs to
     *
     * @return bool true if success false otherwise
     */
    public function create($transition_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);

        $sql = "INSERT INTO $this->table_name (transition_id) 
                VALUES ($transition_id)";

        return $this->updateAndGetLastId($sql);
    }

    /**
     * Update postaction entry
     *
     * @param int   $id       The id of the postaction
     * @param int   $field_id The id of the associated field
     * @param mixed $value    The value_type.
     *
     * @return bool true if success false otherwise
     */
    public function updatePostAction($id, $field_id, $value)
    {
        $id       = $this->da->escapeInt($id);
        $field_id = $this->da->escapeInt($field_id);
        $value    = $this->escapeValue($value);

        $sql = "UPDATE $this->table_name
                SET field_id                 = $field_id,
                    $this->value_column_name = $value
                WHERE id = $id";

        return $this->update($sql);
    }

    /**
     * Create a full-featured postaction.
     *
     * @param int   $transition_id
     * @param int   $field_id
     * @param mixed $value
     *
     * @return bool
     */
    public function save($transition_id, $field_id, $value)
    {
        if (($post_action_id = $this->create($transition_id)) > 0) {
            $this->updatePostAction($post_action_id, $field_id, $value);
        }
    }

    /**
     * Search all postactions belonging to a transition
     *
     * @param int $transition_id The id of the transition
     *
     * @return DataAccessResult
     */
    public function searchByTransitionId($transition_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);

        $sql = "SELECT * 
                FROM $this->table_name
                WHERE transition_id = $transition_id
                ORDER BY id";

        return $this->retrieve($sql);
    }

    /**
     * Search all postactions belonging to a transition and a field
     *
     * Useful to know if a field is already used in a post action of a transition
     *
     * @param int $transition_id The id of the transition
     * @param int $field_id      The id of the field
     *
     * @return DataAccessResult
     */
    public function searchByTransitionIdAndFieldId($transition_id, $field_id)
    {
        $field_id      = $this->da->escapeInt($field_id);
        $transition_id = $this->da->escapeInt($transition_id);

        $sql = "SELECT * 
                FROM $this->table_name
                WHERE field_id      = $field_id 
                  AND transition_id = $transition_id
                ORDER BY id";

        return $this->retrieve($sql);
    }

    /**
     * Search all postactions belonging to a field
     *
     * Useful to know if a field is already used in a post action
     *
     * @param int $field_id The id of the field
     *
     * @return DataAccessResult
     */
    public function searchByFieldId($field_id)
    {
        $field_id = $this->da->escapeInt($field_id);

        $sql = "SELECT * 
                FROM $this->table_name
                WHERE field_id = $field_id 
                ORDER BY id";

        return $this->retrieve($sql);
    }

    /**
     * Like searchByFieldId, but returns the matching fields count.
     *
     * @param int $field_id The id of the field
     *
     * @return int
     */
    public function countByFieldId($field_id)
    {
        return count($this->searchByFieldId($field_id));
    }

    /**
     * Delete a postaction entry
     *
     * @param int $id The id of the postaction
     *
     * @return bool true if success false otherwise
     */
    public function deletePostAction($id)
    {
        $id = $this->da->escapeInt($id);

        $sql = "DELETE
                FROM $this->table_name
                WHERE id = $id";

        return $this->update($sql);
    }

    /**
     * Delete a postaction entry by transition id
     *
     * @param int $id The id of the transition
     *
     * @return bool true if success false otherwise
     */
    public function deletePostActionsByTransitionId($transition_id)
    {
        $transition_id = $this->da->escapeInt($transition_id);

        $sql = "DELETE 
                FROM $this->table_name 
                WHERE transition_id = $transition_id";

        return $this->update($sql);
    }

   /**
    * Delete a postaction entries by workflow_id
    *
    * @param int $id The id of the workflow
    *
    * @return bool true if success false otherwise
    */
    public function deletePostActionsByWorkflowId($workflow_id)
    {
        $workflow_id = $this->da->escapeInt($workflow_id);

        $sql = "DELETE P
                FROM       $this->table_name           AS P
                INNER JOIN tracker_workflow_transition AS T ON P.transition_id = T.transition_id
                WHERE T.workflow_id = $workflow_id";

        return $this->update($sql);
    }

    /**
     * Duplicate a postaction
     *
     * @param int $from_transition_id The id of the template transition
     * @param int $to_transition_id The id of the transition
     * @param int $from_field_id The id of the field (from template)
     * @param int $to_field_id The id of the field
     *
     * @return bool true if success false otherwise
     */
    public function duplicate($from_transition_id, $to_transition_id, $from_field_id, $to_field_id)
    {
        $from_transition_id = $this->da->escapeInt($from_transition_id);
        $to_transition_id   = $this->da->escapeInt($to_transition_id);
        $from_field_id      = $this->da->escapeInt($from_field_id);
        $to_field_id        = $this->da->escapeInt($to_field_id);

        $sql = "INSERT INTO $this->table_name (transition_id, field_id, $this->value_column_name)
                SELECT $to_transition_id, $to_field_id, $this->value_column_name
                FROM $this->table_name
                WHERE field_id      = $from_field_id
                AND   transition_id = $from_transition_id";

        return $this->update($sql);
    }
}
