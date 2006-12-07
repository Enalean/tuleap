<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//	Written for CodeX by Marc Nazarian
//

require_once('common/tracker/ArtifactFieldSet.class.php');

$GLOBALS['Language']->loadLanguageMsg('tracker/tracker');

// Sort by rank result
function art_fieldset_factory_cmp_place($fieldset1, $fieldset2) {
    if ($fieldset1->getRank() < $fieldset2->getRank())
		return -1;
    else if ($fieldset1->getRank() > $fieldset2->getRank())
		return 1;
    return 0;
}

class ArtifactFieldSetFactory extends Error {

    /**
     * The ArtifactType object.
     *
     * @var Object{ArtifactType} $ArtifactType.
     */
    var $ArtifactType;

    /**
     * The ArtifactFieldSet array.
     *
     * @var array of ArtifactFieldSet.
     */
    var $ArtifactFieldSets;

    /**
     *  Constructor.
     *
     *	@param	object $ArtifactType The ArtifactType object to which this ArtifactFieldSetFactory is associated
     *	@return	boolean	success.
     */
    function ArtifactFieldSetFactory($ArtifactType) {
        $this->Error();
        if ( $ArtifactType ) {
            if ($ArtifactType->isError()) {
                $this->setError('ArtifactFieldSetFactory:: '.$ArtifactType->getErrorMessage());
                return false;
            }
            $this->ArtifactType = $ArtifactType;
            $this->ArtifactFieldSets = array();
            $this->fetchData($this->ArtifactType->getID());
        }
        
        return true;
    }

    /**
     *	getArtifactType - get the ArtifactType object this ArtifactFieldSet is associated with.
     *
     *	@return	object The ArtifactType object.
     */
    function getArtifactType() {
        return $this->ArtifactType;
    }
    
    /**
     *  Retrieve the fieldsets associated with an artifact type
     *
     *  @param int $group_artifact_id the artifact type id
     *	@return	boolean	success
     */
    function fetchData($group_artifact_id) {
        
        $sql = "SELECT * 
                FROM artifact_field_set 
                WHERE group_artifact_id=".$group_artifact_id." 
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
    function getArtifactFieldSetsFromId($group_artifact_id) {
        global $Language;

        $sql = "SELECT * 
                FROM artifact_field_set 
                WHERE group_artifact_id='". $group_artifact_id ."'
                ORDER BY rank ASC";

        $result = db_query ($sql);
        $rows = db_numrows($result);
        $myArtifactFieldSets = array();
        
        if (!$result || $rows < 1) {
            $this->setError($Language->getText('tracker_common_type','none_found').' '.db_error());
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
    function getFieldSetById($field_set_id) {
        $fieldsets = $this->ArtifactFieldSets;
        $searched_fieldset = null;
        $found = false;
        while (!$found && list($index,$fieldset) = each($fieldsets)) {
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
    function getAllFieldSets() {
        return $this->ArtifactFieldSets;
    }

    /**
     * getAllFieldSetsContainingUsedFields - returns an array of fieldsets that contain used fields in the tracker
     * use for a field means that the 'use this field' checkbox has been checked,
     * and use for a field set means that there at least one use field inside
     *
     * @return array{ArtifactFieldSet} the field set that contain at least one field used
     */
    function getAllFieldSetsContainingUsedFields() {
        $fieldsets = $this->ArtifactFieldSets;
        $used_fieldsets = array();
        foreach($fieldsets as $fieldset) {
            $fields = $fieldset->getArtifactFields();
            $fieldset_contains_used_field = false;
            // Walk the field list, stop when we find a used field
            while (!$fieldset_contains_used_field && (list($key, $field) = each($fields))) {
                if ($field->isUsed()) {
                    $fieldset_contains_used_field = true;
                }
            }
            // We add the fieldset to the list if there is a used field found
            if ($fieldset_contains_used_field) {
                $used_fieldsets[$fieldset->getID()] = $fieldset;
            }
        }
        uasort($used_fieldsets,"art_fieldset_factory_cmp_place");
        return $used_fieldsets;
    }
    
    
    /**
     * getAllFieldSetsContainingUnusedFields - returns an array of fieldsets that contain unused fields in the tracker
     * unused for a field means that the 'use this field' checkbox has not been checked,
     * and unused for a field set means that there at least one unused field inside
     *
     * @return array{ArtifactFieldSet} the field set that contain at least one field unused
     */
    function getAllFieldSetsContainingUnusedFields() {
        $fieldsets = $this->ArtifactFieldSets;
        $unused_fieldsets = array();
        foreach($fieldsets as $fieldset) {
            $fields = $fieldset->getArtifactFields();
            $fieldset_contains_unused_field = false;
            // Walk the field list, stop when we find an unused field
            while (!$fieldset_contains_unused_field && (list($key, $field) = each($fields))) {
                if (! $field->isUsed()) {
                    $fieldset_contains_unused_field = true;
                }
            }
            // We add the fieldset to the list if there is an unused field found
            if ($fieldset_contains_unused_field) {
                $unused_fieldsets[$fieldset->getID()] = $fieldset;
            }
        }
        uasort($unused_fieldsets,"art_fieldset_factory_cmp_place");
        return $unused_fieldsets;
    }

    /**
     *  Create a new fieldSet
     *
     *  @param string $name the field set label
     *  @param string $description the field set description
     *  @param int $rank the rank on screen
     *
     *  @return boolean - succeed or failed
     */
    function createFieldSet($fieldset_name, $description, $rank) {
    
        global $Language;

        // Check arguments
        if ($fieldset_name=="" ) {
            $this->setError($Language->getText('tracker_common_fieldset_factory','label_requ'));
            return false;
        }

        // Default values
        $description = ($description?$description:"");
        $rank = ($rank?$rank:0);
        
        // create the artifact_field_set
        $sql = "INSERT INTO artifact_field_set (group_artifact_id, name, description, rank)
                VALUES (".$this->ArtifactType->getID().",'".$fieldset_name."','".$description."',".$rank.")";

        $res_insert = db_query($sql);
        if (!$res_insert || db_affected_rows($res_insert) <= 0) {
            $this->setError($Language->getText('tracker_common_fieldset_factory','ins_err',array($field_id,$this->ArtifactType->getID(),db_error())));
            return false;
        }
        
        // Reload the fieldset
        $this->fetchData($this->ArtifactType->getID());

        return true;

    }

	/**
	 *	Delete a FieldSet
	 *
	 *  @param int $field_set_id the field set id to delete
	 *	@return	boolean true if the deletion happen without problems, false otherwise
	 */
	function deleteFieldSet($field_set_id) {

        global $Language;
        
        // Check if the field set contains no field
        $sql = "SELECT field_id, label 
                FROM artifact_field 
                WHERE group_artifact_id='". $this->ArtifactType->getID() ."' AND
                      field_set_id='".$field_set_id."'";

        $result = db_query ($sql);
        $num_rows = db_numrows($result);
        if ($num_rows != 0) {
            $this->setError($Language->getText('tracker_common_fieldset_factory','delete_only_empty_fieldset').' '.db_error());
            return false;
        } else {
            // Delete the FieldSet
            $sql = "DELETE FROM artifact_field_set 
                    WHERE field_set_id=". $field_set_id;
            $result = db_query ($sql);
            if (!$result || db_affected_rows($result) <= 0) {
                $this->setError('Error: deleteArtifactFieldSet '.db_error());
                return false;
            }
        }
        return true;
	}

    /**
	 *	Delete all the FieldSets of this ArtifactType, without checking if they are empty or not.
	 *
	 *	@return	boolean true if the deletion happen without problems, false otherwise
	 */
	function deleteFieldSets() {
        //
		// Delete artifact_field_set records
		//
        $artifact_type = $this->getArtifactType();
	    $sql = 'DELETE FROM artifact_field_set WHERE group_artifact_id='.$artifact_type->getID();
		
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
     *	@return	array the mapping array between the old fieldset_id and the new ones array[$source_fieldset_id] = $dest_fieldset_id, or false if the copy goes wrong
     */
    function copyFieldSets($atid_source,$atid_dest) {
        global $Language;
        //
        // Copy the field_sets
        //
        $fieldset_id_source_dest_array = array();
        $sql_source_fieldset = "SELECT field_set_id, name, description, rank FROM artifact_field_set WHERE group_artifact_id=".$atid_source;
        $res_source_fieldset = db_query($sql_source_fieldset);
        while ($fieldset_source_array = db_fetch_array($res_source_fieldset)) {
            // For each fieldset of the source tracker, we create a new fieldset in the dest tracker,
            // And we remember the association source_fieldset_id <=> dest_fieldset_id to build
            // the association in the copied fields
            // Create a new FieldSet, with the same values than the source one
            $sql_insert_fieldset = "INSERT INTO artifact_field_set VALUES ('', ".$atid_dest.", '".$fieldset_source_array['name']."', '".$fieldset_source_array['description']."', ".$fieldset_source_array['rank'].")";
            $res_insert_fieldset = db_query($sql_insert_fieldset);
            if (!$res_insert_fieldset || db_affected_rows($res_insert_fieldset) <= 0) {
				$this->setError($Language->getText('tracker_common_field_factory','ins_err',array($fieldset_array["field_set_id"],$atid_dest,db_error())));
				return false;
			}
            $dest_fieldset_id = db_insertid($res_insert_fieldset);
            
            // remember the association source_fieldset_id <=> dest_fieldset_id
            $fieldset_id_source_dest_array[$fieldset_source_array['field_set_id']] = $dest_fieldset_id;
        }
        //print_r($fieldset_id_source_dest_array);
        return $fieldset_id_source_dest_array;
    }

}

?>
