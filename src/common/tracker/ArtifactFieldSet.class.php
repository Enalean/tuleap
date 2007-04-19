<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//  Written for CodeX by Marc Nazarian
//

$GLOBALS['Language']->loadLanguageMsg('tracker/tracker');

class ArtifactFieldSet extends Error {

    /**
     * The ArtifactType Object
     *
     * @var	object{ArtifactType} $ArtifactType
     */
	var $ArtifactType;

    /**
     * The ID of this ArtifactfieldSet
     *
     * @var	int $field_set_id
     */
    var $field_set_id;

    /**
     * The name of this ArtifactFieldSet
     *
     * @var	string $name
     */
    var $name;

    /**
     * The description of this ArtifactFieldSet
     *
     * @var	string $description
     */
    var $description;

    /**
     * The rank of this ArtifactFieldSet
     *
     * @var	string $rank
     */
    var $rank;
    
    /**
	 * The array of ArtifactFields Object contained in this ArtifactFieldSet
	 *
	 * @var	array of object{ArtifactField} $ArtifactFields
	 */
    var $ArtifactFields;
    
    /**
     *	ArtifactFieldSet - constructor.
     */
    function ArtifactFieldSet() {
        // Error constructor
		$this->Error();
    }

    /**
     *  fetchData - re-fetch the data for this ArtifactFieldSet from the database.
     *
     *  @return boolean	success.
     */
    function fetchData() {
        global $Language;
    
        $sql = "SELECT * 
                FROM artifact_field_set 
                WHERE artifact_field_set_id=".$this->getID()."";
        $res = db_query($sql);
        if (!$res || db_numrows($res) < 1) {
            $this->setError('ArtifactFieldSet: '.$Language->getText('tracker_common_fieldset','invalid_at'));
            return false;
        }
        // set the attributes of this fieldset
        $this->setFromArray($res);
        // attach the fields of this fieldset
        $art_field_fact = new ArtifactFieldFactory($ArtifactType);
        $this->ArtifactFields = $art_field_fact->getFieldsContainedInFieldSet($this->getID());
        db_free_result($res);
        return true;
    }

    /**
	 *  Set the attributes values
	 *
	 * @param array $fieldset_array the values array
	 * @return void
	 */
	function setFromArray($fieldset_array) {
		$this->field_set_id = $fieldset_array['field_set_id'];
		$this->group_artifact_id = $fieldset_array['group_artifact_id'];
		$this->name = $fieldset_array['name'];
		$this->description = $fieldset_array['description'];
		$this->rank = $fieldset_array['rank'];
	}
    
    /**
	 *  Set the fields of this field set
	 *
	 * @param array{ArtifactFiel} $fields the array of fields contained in this Field set
	 */
	function setArtifactFields($fields) {
		$this->ArtifactFields = $fields;
	}
    /**
     * getArtifactFields - get the ArtifactField objects contained in this ArtifactFieldSet
     *
     * @return array{ArtifactFields} The ArtifactType object.
     */
    function getArtifactFields() {
        return $this->ArtifactFields;
    }
    
    /**
     * getArtifactType - get the ArtifactType object this ArtifactFieldSet is associated with.
     *
     * @return Object{ArtifactType} The ArtifactType object.
     */
    function &getArtifactType() {
        return $this->ArtifactType;
    }
    
    /**
     * getID - get this ArtifactFieldSetID.
     *
     * @return int The field_set_id #.
     */
    function getID() {
        return $this->field_set_id;
    }
    
    /**
     * getArtifactTypeID - get this ArtifactType ID.
     *
     * @return int The group_artifact_id #.
     */
    function getArtifactTypeID() {
        return $this->group_artifact_id;
    }
    
    /**
     * getName - the name of this ArtifactFieldSet
     *
     * @return string name.
     */
    function getName() {
        return $this->name;
    }
    
    /**
     * getDescription - the description of this ArtifactFieldSet
     *
     * @return string description.
     */
    function getDescription() {
        return $this->description;
    }
    
    /**
     * getRank - the rank of this ArtifactFieldSet
     *
     * @return int rank
     */
    function getRank() {
        return $this->rank;
    }

    /**
     * getLabel - the label of this ArtifactFieldSet
     * The tracker label can be internationalized.
     * To do this, fill the name field with the ad-hoc format.
     *
     * @return string label, the name if the name is not internationalized, or the localized text if so
     */
    function getLabel() {
        global $Language;
        if ($this->isNameMustBeLocalized()) {
            return $Language->getText('tracker_common_fieldset', $this->getName());
        } else {
            return $this->getName();
        }
    }
    
    /**
     * getDescriptionText - the text of the description of this ArtifactFieldSet
     * The tracker descripiton can be internationalized.
     * To do this, fill the description field with the ad-hoc format.
     *
     * @return string description, the description text if the description is not internationalized, or the localized text if so
     */
    function getDescriptionText() {
        global $Language;
        if ($this->isDescriptionMustBeLocalized()) {
            return $Language->getText('tracker_common_fieldset', $this->getDescription());
        } else {
            return $this->getDescription();
        }
    }
    
    /**
     * Returns if the fieldset name must be localized or not.
     * The field set name must be localized if the name looks like fieldset_{$fieldset_id}_lbl_key
     *
     * @return true if the fieldset name must be localized, false otherwise.
     */
    function isNameMustBeLocalized() {
        $pattern = "fieldset_(.*)_lbl_key";
        return ereg($pattern, $this->getName());
    }
    
    /**
     * Returns if the fieldset description must be localized or not.
     * The field set description must be localized if the name looks like fieldset_{$fieldset_id}_desc_key
     *
     * @return true if the fieldset description must be localized, false otherwise.
     */
    function isDescriptionMustBeLocalized() {
        $pattern = "fieldset_(.*)_desc_key";
        return ereg($pattern, $this->getDescription());
    }
    
    /**
	 *  Return all the fields used contained in this fieldset
	 *
	 *	@return	array{ArtifactField}
	 */
	function getAllUsedFields() {
        $result_fields = array();
        while (list($key,$field) = each($this->ArtifactFields) ) {
            if ( $field->IsUsed() ) {
                $result_fields[$key] = $field;
            }
        }
        return $result_fields;
	}

	/**
	 *  Return all the fields unused contained in this fieldset
	 *
	 *	@return	array{ArtifactField}
	 */
	function getAllUnusedFields() {
        $result_fields = array();
        while (list($key,$field) = each($this->ArtifactFields) ) {
            if ( ! $field->IsUsed() ) {
                $result_fields[$key] = $field;
            }
        }
        return $result_fields;
	}
    
    /** 
     * userCanSubmit : returns true if user has Submit permission on this fieldset (this means that at least one field of this fieldset has submit permissions)
     *
     * @param user_id: if not given or 0 take the current user
     */ 
    function userCanSubmit($group_id, $group_artifact_id, $user_id = 0) {
        $pm =& PermissionsManager::instance();
        $um =& UserManager::instance();
        $user =& $um->getUserById($user_id);
        if ($user->isSuperUser()) {
            $ok = true;
        } else {
            $ok = false;
            $fields = $this->getAllUsedFields();
            while (!$ok && list(,$field) = each($fields)) {
                if (!$field->isSpecial()) {
                    $ok = $field->userCanSubmit($group_id, $group_artifact_id, $user_id);
                }
            }
        }
        return $ok;
    }

    /**
     *  update - use this to update this ArtifactFieldSet in the database.
     *
     *  @param	string	The item name.
     *  @param	string	The item description.
     *  @param	int		The rank.
     *  @return true on success, false on failure.
     */
    function update($name, $description, $rank) {
        global $Language;
        
        if (!$name || trim($name) == "") {
            $this->setError('ArtifactType: '.$Language->getText('tracker_common_fieldset','name_requ'));
            return false;
        }
        
        $sql = "UPDATE artifact_field_set 
                SET name='".$name."', 
                    description='".$description."',
                    rank='$rank'
                WHERE field_set_id='". $this->getID() ."'";

        $res=db_query($sql);
        if (!$res) {
            $this->setError('ArtifactFieldSet::Update(): '.db_error());
            return false;
        } else {
            $this->fetchData();
            return true;
        }
    }

}

?>
