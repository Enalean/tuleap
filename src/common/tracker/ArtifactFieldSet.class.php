<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

class ArtifactFieldSet
{

    /**
     * The ArtifactType Object
     *
     * @var    object{ArtifactType} $ArtifactType
     */
    public $ArtifactType;

    /**
     * The ID of this ArtifactfieldSet
     *
     * @var    int $field_set_id
     */
    public $field_set_id;

    /**
     * The name of this ArtifactFieldSet
     *
     * @var    string $name
     */
    public $name;

    /**
     * The description of this ArtifactFieldSet
     *
     * @var    string $description
     */
    public $description;

    /**
     * The rank of this ArtifactFieldSet
     *
     * @var    string $rank
     */
    public $rank;

    /**
     * The array of ArtifactFields Object contained in this ArtifactFieldSet
     *
     * @var    array of object{ArtifactField} $ArtifactFields
     */
    public $ArtifactFields;

    /**
     *    ArtifactFieldSet - constructor.
     */
    public function __construct()
    {
    }

    /**
     *  fetchData - re-fetch the data for this ArtifactFieldSet from the database.
     *
     *  @return bool success.
     */
    public function fetchData()
    {
        $sql = "SELECT *
                FROM artifact_field_set
                WHERE artifact_field_set_id=" . db_ei($this->getID()) . "";
        $res = db_query($sql);
        if (!$res || db_numrows($res) < 1) {
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
    public function setFromArray($fieldset_array)
    {
        $this->field_set_id = $fieldset_array['field_set_id'];
        $this->group_artifact_id = $fieldset_array['group_artifact_id'];
        $this->name = $fieldset_array['name'];
        $this->description = $fieldset_array['description'];
        $this->rank = $fieldset_array['rank'];
    }

    /**
     *  Set the fields of this field set
     *
     * @param ArtifactField[] $fields the array of fields contained in this Field set
     */
    public function setArtifactFields($fields)
    {
        $this->ArtifactFields = $fields;
    }
    /**
     * getArtifactFields - get the ArtifactField objects contained in this ArtifactFieldSet
     *
     * @return ArtifactField[] The ArtifactType object.
     */
    public function getArtifactFields()
    {
        return $this->ArtifactFields;
    }

    /**
     * getArtifactType - get the ArtifactType object this ArtifactFieldSet is associated with.
     *
     * @return Object{ArtifactType} The ArtifactType object.
     */
    public function &getArtifactType()
    {
        return $this->ArtifactType;
    }

    /**
     * getID - get this ArtifactFieldSetID.
     *
     * @return int The field_set_id #.
     */
    public function getID()
    {
        return $this->field_set_id;
    }

    /**
     * getArtifactTypeID - get this ArtifactType ID.
     *
     * @return int The group_artifact_id #.
     */
    public function getArtifactTypeID()
    {
        return $this->group_artifact_id;
    }

    /**
     * getName - the name of this ArtifactFieldSet
     *
     * @return string name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * getDescription - the description of this ArtifactFieldSet
     *
     * @return string description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * getRank - the rank of this ArtifactFieldSet
     *
     * @return int rank
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * getLabel - the label of this ArtifactFieldSet
     * The tracker label can be internationalized.
     * To do this, fill the name field with the ad-hoc format.
     *
     * @return string label, the name if the name is not internationalized, or the localized text if so
     */
    public function getLabel()
    {
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
    public function getDescriptionText()
    {
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
    public function isNameMustBeLocalized()
    {
        $pattern = "/fieldset_(.*)_lbl_key/";
        return preg_match($pattern, $this->getName());
    }

    /**
     * Returns if the fieldset description must be localized or not.
     * The field set description must be localized if the name looks like fieldset_{$fieldset_id}_desc_key
     *
     * @return true if the fieldset description must be localized, false otherwise.
     */
    public function isDescriptionMustBeLocalized()
    {
        $pattern = "/fieldset_(.*)_desc_key/";
        return preg_match($pattern, $this->getDescription());
    }

    /**
     *  Return all the fields used contained in this fieldset
     *
     *    @return    array{ArtifactField}
     */
    public function getAllUsedFields()
    {
        $result_fields = array();
        foreach ($this->ArtifactFields as $key => $field) {
            if ($field->IsUsed()) {
                $result_fields[$key] = $field;
            }
        }
        return $result_fields;
    }

    /**
     *  Return all the fields unused contained in this fieldset
     *
     *    @return    array{ArtifactField}
     */
    public function getAllUnusedFields()
    {
        $result_fields = array();
        foreach ($this->ArtifactFields as $key => $field) {
            if (! $field->IsUsed()) {
                $result_fields[$key] = $field;
            }
        }
        return $result_fields;
    }

    /**
     * userCanSubmit : returns true if user has Submit permission on this fieldset (this means that at least one field of this fieldset has submit permissions)
     *
     * @param user_id: if not given or false take the current user
     */
    public function userCanSubmit($group_id, $group_artifact_id, $user_id = false)
    {
        $um = UserManager::instance();
        if (! $user_id) {
            $user = $um->getCurrentUser();
            $user_id = $user->getId();
        } else {
            $user = $um->getUserById($user_id);
        }
        if ($user->isSuperUser()) {
            $ok = true;
        } else {
            $ok = false;
            $fields = $this->getAllUsedFields();
            foreach ($fields as $field) {
                if ($ok) {
                    break;
                }
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
     *  @param    string    The item name.
     *  @param    string    The item description.
     *  @param    int        The rank.
     *  @return true on success, false on failure.
     */
    public function update($name, $description, $rank)
    {
        if (!$name || trim($name) == "") {
            return false;
        }

        $sql = "UPDATE artifact_field_set
                SET name='" . db_es($name) . "',
                    description='" . db_es($description) . "',
                    rank='" . db_ei($rank) . "'
                WHERE field_set_id='" .  db_ei($this->getID())  . "'";

        $res = db_query($sql);
        if (!$res) {
            return false;
        } else {
            $this->fetchData();
            return true;
        }
    }
}
