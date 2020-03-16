<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

// Sort by rank result
function art_fieldset_factory_cmp_place($fieldset1, $fieldset2)
{
    if ($fieldset1->getRank() < $fieldset2->getRank()) {
        return -1;
    } elseif ($fieldset1->getRank() > $fieldset2->getRank()) {
        return 1;
    }
    return 0;
}

class ArtifactFieldSetFactory
{

    /**
     * The ArtifactType object.
     *
     * @var Object{ArtifactType} $ArtifactType.
     */
    public $ArtifactType;

    /**
     * The ArtifactFieldSet array.
     *
     * @var array of ArtifactFieldSet.
     */
    public $ArtifactFieldSets;
    /**
     * @var string
     */
    private $error_message = '';
    /**
     * @var bool
     */
    private $error_state = false;

    /**
     *
     *
     *    @param    object $ArtifactType The ArtifactType object to which this ArtifactFieldSetFactory is associated
     *    @return bool success.
     */
    public function __construct($ArtifactType)
    {
        if ($ArtifactType) {
            if ($ArtifactType->isError()) {
                $this->setError('ArtifactFieldSetFactory:: ' . $ArtifactType->getErrorMessage());
                return false;
            }
            $this->ArtifactType = $ArtifactType;
            $this->ArtifactFieldSets = array();
            $this->fetchData($this->ArtifactType->getID());
        }

        return true;
    }

    /**
     *    getArtifactType - get the ArtifactType object this ArtifactFieldSet is associated with.
     *
     *    @return    object The ArtifactType object.
     */
    public function getArtifactType()
    {
        return $this->ArtifactType;
    }

    /**
     *  Retrieve the fieldsets associated with an artifact type
     *
     *  @param int $group_artifact_id the artifact type id
     *    @return bool success
     */
    public function fetchData($group_artifact_id)
    {
        $sql = "SELECT *
                FROM artifact_field_set
                WHERE group_artifact_id=" . db_ei($group_artifact_id) . "
                ORDER BY rank";

        //echo $sql;

        $res = db_query($sql);
        $this->ArtifactFieldSets = array();
        while ($fieldset_array = db_fetch_array($res)) {
            // create a new ArtifactFieldSet (empty)
            $fieldset = new ArtifactFieldSet();
            // set the datas
            $fieldset->setFromArray($fieldset_array);
            // set the fields contained inside the FieldSet
            $art_field_fact = new ArtifactFieldFactory($this->ArtifactType);
            $fields = $art_field_fact->getFieldsContainedInFieldSet($fieldset_array['field_set_id']);
            $fieldset->setArtifactFields($fields);

            $this->ArtifactFieldSets[] = $fieldset;
        }

        reset($this->ArtifactFieldSets);
    }

    /**
     *  getArtifactFieldSetsFromId - set the array of ArtifactFieldSet objects for the ArtifactType of id $group_artifact_id
     *
     *  @param int $group_artifact_id the id of the tracker
     */
    public function getArtifactFieldSetsFromId($group_artifact_id)
    {
        global $Language;

        $sql = "SELECT *
                FROM artifact_field_set
                WHERE group_artifact_id='" . db_ei($group_artifact_id) . "'
                ORDER BY rank ASC";

        $result = db_query($sql);
        $rows = db_numrows($result);
        $myArtifactFieldSets = array();

        if (!$result || $rows < 1) {
            $this->setError($Language->getText('tracker_common_type', 'none_found') . ' ' . db_error());
            return false;
        } else {
            while ($arr = db_fetch_array($result)) {
                $new_artifact_field_set = new ArtifactFieldSet();
                $new_artifact_field_set->setFromArray($arr);
                $myArtifactFieldSets[] = $new_artifact_field_set;
            }
        }
        return $myArtifactFieldSets;
    }

    /**
     * getfieldSetById - return the fieldset of the tracker $ArtifactType whitch have the ID $field_set_id
     *
     * @param int $field_set_id the id of the field set we are looking for
     * @return Object{ArtifactFieldSet} the field set found, or null if not
     */
    public function getFieldSetById($field_set_id)
    {
        $fieldsets = $this->ArtifactFieldSets;
        $searched_fieldset = null;
        $found = false;
        foreach ($fieldsets as $fieldset) {
            if ($found) {
                break;
            }
            if ($fieldset->getID() == $field_set_id) {
                $searched_fieldset = $fieldset;
                $found = true;
            }
        }
        return $searched_fieldset;
    }

    /**
     * getAllFieldSets - returns all the field sets of the tracker
     *
     * @return array{ArtifactFieldSet} all the field sets of the tracker $ArtifactType
     */
    public function getAllFieldSets()
    {
        return $this->ArtifactFieldSets;
    }

    /**
     * getAllFieldSetsContainingUsedFields - returns an array of fieldsets that contain used fields in the tracker
     * use for a field means that the 'use this field' checkbox has been checked,
     * and use for a field set means that there at least one use field inside
     *
     * @return array{ArtifactFieldSet} the field set that contain at least one field used
     */
    public function getAllFieldSetsContainingUsedFields()
    {
        $fieldsets = $this->ArtifactFieldSets;
        $used_fieldsets = array();
        foreach ($fieldsets as $fieldset) {
            $fields = $fieldset->getArtifactFields();
            $fieldset_contains_used_field = false;
            // Walk the field list, stop when we find a used field
            foreach ($fields as $field) {
                if ($fieldset_contains_used_field) {
                    break;
                }
                if ($field->isUsed()) {
                    $fieldset_contains_used_field = true;
                }
            }
            // We add the fieldset to the list if there is a used field found
            if ($fieldset_contains_used_field) {
                $used_fieldsets[$fieldset->getID()] = $fieldset;
            }
        }
        uasort($used_fieldsets, "art_fieldset_factory_cmp_place");
        return $used_fieldsets;
    }


    /**
     * getAllFieldSetsContainingUnusedFields - returns an array of fieldsets that contain unused fields in the tracker
     * unused for a field means that the 'use this field' checkbox has not been checked,
     * and unused for a field set means that there at least one unused field inside
     *
     * @return array{ArtifactFieldSet} the field set that contain at least one field unused
     */
    public function getAllFieldSetsContainingUnusedFields()
    {
        $fieldsets = $this->ArtifactFieldSets;
        $unused_fieldsets = array();
        foreach ($fieldsets as $fieldset) {
            $fields = $fieldset->getArtifactFields();
            $fieldset_contains_unused_field = false;
            // Walk the field list, stop when we find an unused field
            foreach ($fields as $field) {
                if ($fieldset_contains_unused_field) {
                    break;
                }
                if (! $field->isUsed()) {
                    $fieldset_contains_unused_field = true;
                }
            }
            // We add the fieldset to the list if there is an unused field found
            if ($fieldset_contains_unused_field) {
                $unused_fieldsets[$fieldset->getID()] = $fieldset;
            }
        }
        uasort($unused_fieldsets, "art_fieldset_factory_cmp_place");
        return $unused_fieldsets;
    }

    /**
     *  Create a new fieldSet
     *
     *  @param string $name the field set label
     *  @param string $description the field set description
     *  @param int $rank the rank on screen
     *
     *  @return bool - succeed or failed
     */
    public function createFieldSet($fieldset_name, $description, $rank)
    {
        global $Language;

        // Check arguments
        if ($fieldset_name == "") {
            $this->setError($Language->getText('tracker_common_fieldset_factory', 'label_requ'));
            return false;
        }

        // Default values
        $description = ($description ? $description : "");
        $rank = ($rank ? $rank : 0);

        // create the artifact_field_set
        $sql = "INSERT INTO artifact_field_set (group_artifact_id, name, description, rank)
                VALUES (" . db_ei($this->ArtifactType->getID()) . ",'" . db_es($fieldset_name) . "','" . db_es($description) . "'," . db_ei($rank) . ")";

        $res_insert = db_query($sql);
        if (!$res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_fieldset_factory', 'ins_err', array($field_id,$this->ArtifactType->getID(),db_error())));
            return false;
        }

        // Reload the fieldset
        $this->fetchData($this->ArtifactType->getID());

        return true;
    }

    /**
     *    Delete a FieldSet
     *
     *  @param int $field_set_id the field set id to delete
     *    @return bool true if the deletion happen without problems, false otherwise
     */
    public function deleteFieldSet($field_set_id)
    {
        global $Language;

        // Check if the field set contains no field
        $sql = "SELECT field_id, label
                FROM artifact_field
                WHERE group_artifact_id='" .  db_ei($this->ArtifactType->getID())  . "' AND
                      field_set_id='" . db_ei($field_set_id) . "'";

        $result = db_query($sql);
        $num_rows = db_numrows($result);
        if ($num_rows != 0) {
            $this->setError($Language->getText('tracker_common_fieldset_factory', 'delete_only_empty_fieldset') . ' ' . db_error());
            return false;
        } else {
            // Delete the FieldSet
            $sql = "DELETE FROM artifact_field_set
                    WHERE field_set_id=" .  db_ei($field_set_id);
            $result = db_query($sql);
            if (!$result || db_affected_rows($result) <= 0) {
                $this->setError('Error: deleteArtifactFieldSet ' . db_error());
                return false;
            }
        }
        return true;
    }

    /**
     *    Delete all the FieldSets of this ArtifactType, without checking if they are empty or not.
     *
     *    @return bool true if the deletion happen without problems, false otherwise
     */
    public function deleteFieldSets()
    {
     // Delete artifact_field_set records
        $artifact_type = $this->getArtifactType();
        $sql = 'DELETE FROM artifact_field_set WHERE group_artifact_id=' . db_ei($artifact_type->getID());

     //echo $sql;

        $res = db_query($sql);
        if (!$res) {
            return false;
        }
        return true;
    }


    /**
     *
     *  Copy all the fieldsets informations from a tracker to another.
     *
     *  @param atid_source: source tracker
     *  @param atid_dest: destination tracker
     *
     *    @return    array the mapping array between the old fieldset_id and the new ones array[$source_fieldset_id] = $dest_fieldset_id, or false if the copy goes wrong
     */
    public function copyFieldSets($atid_source, $atid_dest)
    {
        global $Language;
        // Copy the field_sets
        $fieldset_id_source_dest_array = array();
        $sql_source_fieldset = "SELECT field_set_id, name, description, rank FROM artifact_field_set WHERE group_artifact_id=" . db_ei($atid_source);
        $res_source_fieldset = db_query($sql_source_fieldset);
        while ($fieldset_source_array = db_fetch_array($res_source_fieldset)) {
            // For each fieldset of the source tracker, we create a new fieldset in the dest tracker,
            // And we remember the association source_fieldset_id <=> dest_fieldset_id to build
            // the association in the copied fields
            // Create a new FieldSet, with the same values than the source one
            $sql_insert_fieldset = "INSERT INTO artifact_field_set VALUES ('', " . db_ei($atid_dest) . ", '" .
                db_es($fieldset_source_array['name']) . "', '" .
                db_es($fieldset_source_array['description']) . "', " .
                db_ei($fieldset_source_array['rank']) . ")";
            $res_insert_fieldset = db_query($sql_insert_fieldset);
            if (!$res_insert_fieldset || db_affected_rows($res_insert_fieldset) <= 0) {
                $this->setError($Language->getText('tracker_common_fieldset_factory', 'ins_err', array($fieldset_source_array["field_set_id"],$atid_dest,db_error())));
                return false;
            }
            $dest_fieldset_id = db_insertid($res_insert_fieldset);

            // remember the association source_fieldset_id <=> dest_fieldset_id
            $fieldset_id_source_dest_array[$fieldset_source_array['field_set_id']] = $dest_fieldset_id;
        }
        //print_r($fieldset_id_source_dest_array);
        return $fieldset_id_source_dest_array;
    }

    /**
     * @param $string
     */
    public function setError($string)
    {
        $this->error_state = true;
        $this->error_message = $string;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->error_state) {
            return $this->error_message;
        } else {
            return $GLOBALS['Language']->getText('include_common_error', 'no_err');
        }
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->error_state;
    }
}
