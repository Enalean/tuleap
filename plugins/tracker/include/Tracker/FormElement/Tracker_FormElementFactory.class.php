<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('dao/Tracker_FormElement_FieldDao.class.php');

require_once('Tracker_FormElement_Field_Integer.class.php');
require_once('Tracker_FormElement_Field_Float.class.php');
require_once('Tracker_FormElement_Field_Text.class.php');
require_once('Tracker_FormElement_Field_String.class.php');
require_once('Tracker_FormElement_Field_Date.class.php');
require_once('Tracker_FormElement_Field_Selectbox.class.php');
require_once('Tracker_FormElement_Field_MultiSelectbox.class.php');
require_once('Tracker_FormElement_Field_ArtifactId.class.php');
require_once('Tracker_FormElement_Field_File.class.php');
require_once('Tracker_FormElement_Field_OpenList.class.php');
require_once('Tracker_FormElement_Field_LastUpdateDate.class.php');
require_once('Tracker_FormElement_Field_SubmittedBy.class.php');
require_once('Tracker_FormElement_Field_SubmittedOn.class.php');
require_once('Tracker_FormElement_Field_ArtifactLink.class.php');
require_once('Tracker_FormElement_Field_PermissionsOnArtifact.class.php');
require_once('Tracker_FormElement_Field_CrossReferences.class.php');
require_once('Tracker_FormElement_Container_Fieldset.class.php');
require_once('Tracker_FormElement_Container_Column.class.php');
require_once('Tracker_FormElement_StaticField_LineBreak.class.php');
require_once('Tracker_FormElement_StaticField_Separator.class.php');
require_once('Tracker_FormElement_StaticField_RichText.class.php');
require_once('common/widget/Widget_Static.class.php');

class Tracker_FormElementFactory {
    
    /**
     * Cache formElements
     */
    protected $formElements           = array();
    protected $formElements_by_parent = array();
    protected $formElements_by_name   = array();
    protected $used_formElements      = array();
    protected $used                   = array();
    
    // Please use unique key for each element
    protected $classnames             = array(
        'string'   => 'Tracker_FormElement_Field_String',
        'text'     => 'Tracker_FormElement_Field_Text',
        'sb'       => 'Tracker_FormElement_Field_Selectbox',
        'msb'      => 'Tracker_FormElement_Field_MultiSelectbox',
        'date'     => 'Tracker_FormElement_Field_Date',
        'file'     => 'Tracker_FormElement_Field_File',
        'int'      => 'Tracker_FormElement_Field_Integer',
        'float'    => 'Tracker_FormElement_Field_Float',
        'tbl'      => 'Tracker_FormElement_Field_OpenList',
        'art_link' => 'Tracker_FormElement_Field_ArtifactLink',
        'perm'     => 'Tracker_FormElement_Field_PermissionsOnArtifact',
    );
    
    protected $special_classnames     = array(
        'aid'      => 'Tracker_FormElement_Field_ArtifactId',
        'lud'      => 'Tracker_FormElement_Field_LastUpdateDate',
        'subby'    => 'Tracker_FormElement_Field_SubmittedBy',
        'subon'    => 'Tracker_FormElement_Field_SubmittedOn',
        'cross' => 'Tracker_FormElement_Field_CrossReferences',
    );
    protected $group_classnames       = array(
        'fieldset' => 'Tracker_FormElement_Container_Fieldset',
        'column'   => 'Tracker_FormElement_Container_Column',
    );
    protected $staticfield_classnames = array(
        'linebreak'   => 'Tracker_FormElement_StaticField_LineBreak',
        'separator'   => 'Tracker_FormElement_StaticField_Separator',
        'staticrichtext' => 'Tracker_FormElement_StaticField_RichText',
    );
    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct() {
    }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;
    
    /**
     * The singleton method
     * 
     * @return Tracker_FormElementFactory
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }
    
    /**
     * Returns the short name of the type of the given field
     * 
     * @param Tracker_FormElement $formElement
     * @return string 
     */
    public function getType($formElement) {
        return array_search(get_class($formElement), array_merge($this->classnames,
                                                                 $this->special_classnames, 
                                                                 $this->group_classnames, 
                                                                 $this->staticfield_classnames));
    }
    
    /**
     * Return the prefix for field name
     *
     * @param the type of the field
     *
     * @return string the name
     */
    protected function getPrefixNameForType($type) {
        $prefix = 'field_';
        if (isset($this->group_classnames[$type]) || isset($this->special_classnames[$type]) || isset($this->staticfield_classnames[$type])) {
            $prefix = $type;
        }
        return $prefix;
    }
    
    /**
     * Get a formElement by id
     * @param int $id the id of the formElement to retrieve
     * @return Tracker_FormElement_Field
     */
    function getFormElementById($id) {
        if (!isset($this->formElements[$id])) {
            $this->formElements[$id] = null;
            $row = $this->getDao()->searchById($id)->getRow();
            if ($row) {
                $this->formElements[$id] = $this->getInstanceFromRow($row);
            }
        }
        return $this->formElements[$id];
    }
    
    /**
     * Get a formElement by its short name
     * @param int $tracker_id the tracker of the formElement to retrieve
     * @param string $name the name of the formElement to retrieve
     * @return Tracker_FormElement_Field
     */
    function getFormElementByName($tracker_id, $name) {
        if (!isset($this->formElements_by_name[$tracker_id][$name])) {
            $f = null;
            $row = $this->getDao()->searchByTrackerIdAndName($tracker_id, $name)->getRow();
            if ($row) {
                if (!isset($this->formElements[$row['id']])) {
                    $f = $this->getInstanceFromRow($row);
                    $this->formElements[$row['id']] = $f;
                } else {
                    $f = $this->formElements[$row['id']];
                }
            }
            $this->formElements_by_name[$tracker_id][$name] = $f;
        }
        return $this->formElements_by_name[$tracker_id][$name];
    }
    
    /**
     * Get a formElement by id
     * @param int $id the id of the formElement to retrieve
     * @return Tracker_FormElement_Field
     */
    function getUsedFormElementById($id) {
        if (!isset($this->used_formElements[$id])) {
            $this->used_formElements[$id] = null;
            $formElement = $this->getFormElementById($id);
            if ($formElement && $formElement->isUsed()) {
                $this->used_formElements[$id] = $formElement;
            }
        }
        return $this->used_formElements[$id];
    }
    
    /**
     * Get a used field by name
     *
     * @param int    $tracker_id the id of the tracker
     * @param string $field_name the name of the field (short name)
     *
     * @return Tracker_FormElement_Field, or null if not found
     */
    function getUsedFieldByName($tracker_id, $field_name) {
        $f = null;
        $row = $this->getDao()->searchUsedByTrackerIdAndName($tracker_id, $field_name)->getRow();
        if ($row) {
            if (!isset($this->formElements[$row['id']])) {
                $f = $this->getInstanceFromRow($row);
                $this->formElements[$row['id']] = $f;
            } else {
                $f = $this->formElements[$row['id']];
            }
        }
        return $f;
    }
    
    /**
     * Get used formElements by parent id
     * @param int parent_id
     * @return array
     */
    public function getUsedFormElementsByParentId($parent_id) {
        if (!isset($this->formElements_by_parent[$parent_id])) {
            $this->formElements_by_parent[$parent_id] = array();
            foreach($this->getDao()->searchUsedByParentId($parent_id) as $row) {
                if (!isset($this->formElements[$row['id']])) {
                    $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
                }
                if ($this->formElements[$row['id']]) {
                    $this->formElements_by_parent[$parent_id][$row['id']] = $this->formElements[$row['id']];
                }
            }
        }
        return $this->formElements_by_parent[$parent_id];
    }
    
    /**
     * Get all formElements by tracker id
     *
     * @param Tracker $tracker
     *
     * @return array
     */
    public function getAllFormElementsForTracker($tracker) {
        $all = array();
        foreach($this->getDao()->searchByTrackerId($tracker->getId()) as $row) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            if ($this->formElements[$row['id']]) {
                $all[$row['id']] = $this->formElements[$row['id']];
            }
        }
        return $all;
    }
    
    /**
     * Get all formElements by parent id
     * @param int parent_id
     * @return array
     */
    public function getAllFormElementsByParentId($parent_id) {
        $all = array();
        foreach($this->getDao()->searchByParentId($parent_id) as $row) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            $all[$row['id']] = $this->formElements[$row['id']];
        }
        return $all;
    }
    
    /**
     * 
     * @todo Check the type of the field. 
     *
     * @return Tracker_FormElement_Field or null if not found or not a Field
     */
    public function getFieldById($id) {
        $field = $this->getFormElementById($id);
        if (!is_a($field, 'Tracker_FormElement_Field')) {
            $field = null;
        }
        return $field;
    }
    
    /**
     * @param Tracker $tracker
     * @return array of Tracker_FormElement - All fields used by the tracker
     */
    public function getUsedFields($tracker) {
        $field_classnames = array_merge($this->classnames, $this->special_classnames);
        EventManager::instance()->processEvent('tracker_formElement_classnames', array('classnames' => &$field_classnames));
        return $this->getUsedFormElementsByType($tracker, array_keys($field_classnames));
    }
    
    /**
     * @param Tracker $tracker
     * @return array of Tracker_FormElement - All fields used and  unused by the tracker
     */
    public function getFields($tracker) {
        $field_classnames = array_merge($this->classnames, $this->special_classnames);
        EventManager::instance()->processEvent('tracker_formElement_classnames', array('classnames' => &$field_classnames));
        return $this->getFormElementsByType($tracker, array_keys($field_classnames));
    }
    
    public function getUsedFieldByIdAndType($tracker, $field_id, $type) {
        $field = null;
        if ($row = $this->getDao()->searchUsedByIdAndType($tracker->getId(), $field_id, $type)->getRow()) {
            $field = $this->formElements[$field_id] = $this->getInstanceFromRow($row);
        }
        return $field;
    }
    
    /**
     * @param Tracker $tracker
     * @return array All date formElements used by the tracker
     */
    public function getUsedDateFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, array('date', 'lud', 'subon'));
    }
    
    /**
     * @param Tracker $tracker
     * @return array All int formElements used by the tracker
     */
    public function getUsedIntFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, 'int');
    }
    
    /**
     * @param Tracker $tracker
     * @return array All numeric formElements used by the tracker
     */
    public function getUsedNumericFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, array('int', 'float'));
    }
    
    /**
     * @param Tracker $tracker
     * @return array All (multi) selectboxes formElements used by the tracker
     */
    public function getUsedListFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, array('sb', 'msb', 'tbl'));
    }
    
    /**
     * @param Tracker $tracker
     * @return array All lists formElements bind to users used by the tracker
     */
    public function getUsedUserListFields($tracker) {
        $formElements = array();
        foreach($this->getDao()->searchUsedUserListFieldByTrackerId($tracker->getId()) as $row) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            if ($this->formElements[$row['id']]) {
                $formElements[] = $this->formElements[$row['id']];
            }
        }
        return $formElements;
    }
    
    public function getUsedUserListFieldById($tracker, $field_id) {
        $field = null;
        if ($row = $this->getDao()->getUsedUserListFieldById($tracker->getId(), $field_id)->getRow()) {
            $field = $this->formElements[$field_id] = $this->getInstanceFromRow($row);
        }
        return $field;
    }
    
    /**
     * Returns a list of used field elements of type select box or multi-select box, bind to users
     * neither open lists nor subby fields will be returned.
     *
     * @param Tracker $tracker
     *
     * @return array All (multi)select box lists formElements bind to users used by the tracker
     */
    public function getUsedUserSbFields($tracker) {
        $formElements = array();
        foreach($this->getDao()->searchUsedUserSbFieldByTrackerId($tracker->getId()) as $row) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            if ($this->formElements[$row['id']]) {
                $formElements[] = $this->formElements[$row['id']];
            }
        }
        return $formElements;
    }
    
    public function getUsedUserSbFieldById($tracker, $field_id) {
        $field = null;
        if ($row = $this->getDao()->getUsedUserSbFieldById($tracker->getId(), $field_id)->getRow()) {
            $field = $this->formElements[$field_id] = $this->getInstanceFromRow($row);
        }
        return $field;
    }
    
    public function getUsedListFieldById($tracker, $field_id) {
        return $this->getUsedFieldByIdAndType($tracker, $field_id, array('sb', 'msb', 'tbl'));
    }
    
    public function getUsedSbFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, array('sb', 'msb'));
    }
    
    /**
     * @param Tracker $tracker
     * @return array All text formElements used by the tracker
     */
    public function getUsedTextFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, array('text', 'string', 'ref'));
    }
    
    public function getUsedTextFieldById($tracker, $field_id) {
        return $this->getUsedFieldByIdAndType($tracker, $field_id, array('text', 'string', 'ref'));
    }
    
    /**
     * @param Tracker $tracker
     * @return array All string formElements used by the tracker
     */
    public function getUsedStringFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, array('string', 'ref'));
    }
    
    /**
     * Duplicate a formElement
     * @param int $from_tracker_id
     * @param int $to_tracker_id
     * @param array $ugroup_mapping
     * @return array the mapping between old formElements and new ones
     */
    public function duplicate($from_tracker_id, $to_tracker_id, $ugroup_mapping) {
        $mapping = array();
        
        foreach($this->getDao()->searchByTrackerId($from_tracker_id) as $from_row) {
            $has_workflow = false;
            if($from_row['formElement_type']=='sb') {
                $field = $this->getFieldById($from_row['id']);
                if ($field->fieldHasDefineWorkflow()) {
                    $has_workflow = true;
                }
            }
            //First duplicate formElement info
            if ($id = $this->getDao()->duplicate($from_row['id'], $to_tracker_id)) {
                if (!$has_workflow) {
                    //Then duplicate formElement
                    $mapping[] = array('from' => $from_row['id'], 
                                    'to' => $id,
                                    'values' => $this->getFormElementById($id)->duplicate($from_row['id'], $id),
                                    'workflow'=> false);
                } else {
                    $workflow = $this->getFormElementById($from_row['id'])->getWorkflow();
                    $values = $this->getFormElementById($id)->duplicate($from_row['id'], $id);
                    $mapping[] = array('from' => $from_row['id'],
                                    'to' => $id,
                                    'values' => $values, 
                                    'workflow'=> true);
                }
               
                $type = $this->getType($this->getFormElementById($id));
                $tracker = TrackerFactory::instance()->getTrackerByid($to_tracker_id);                

            }
        }
        $this->getDao()->mapNewParentsAfterDuplication($to_tracker_id, $mapping);
        return $mapping;
    }
    
    /**
     * @param Tracker $tracker
     * @param mixed $type the type (string) or types (array of) you are looking for
     * @return array All text formElements used by the tracker
     */
    public function getFormElementsByType($tracker, $type) {
        return $this->getCachedInstancesFromDAR($this->getDao()->searchUsedByTrackerIdAndType($tracker->id, $type));
    }
    
    /**
     * @param DataAccessResult $dar the db collection of FormElements to instantiate
     * 
     * @return array All text formElements used by the tracker
     */
    protected function getCachedInstancesFromDAR(DataAccessResult $dar) {
        $formElements = array();
        foreach($dar as $row) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            if ($this->formElements[$row['id']]) {
                $formElements[] = $this->formElements[$row['id']];
            }
        }
        return $formElements;
    }
    
    /**
     * @param Tracker $tracker
     * @param mixed $type the type (string) or types (array of) you are looking for
     * @return array All text formElements used by the tracker
     */
    public function getUsedFormElementsByType($tracker, $type) {
        $used = true;
        return $this->getCachedInstancesFromDAR($this->getDao()->searchUsedByTrackerIdAndType($tracker->id, $type, $used));
    }
    
    public function getUnusedFormElementForTracker($tracker) {
        $unused = array();
        foreach($this->getDao()->searchUnusedByTrackerId($tracker->id) as $row) {
            $unused[$row['id']] = $this->getInstanceFromRow($row);
        }
        return $unused;
    }
    
    public function getUsedFormElementForTracker($tracker) {
        if (!isset($this->used[$tracker->id])) {
            $this->used[$tracker->id] = array();
            foreach($this->getDao()->searchUsedByTrackerId($tracker->id) as $row) {
                if (!isset($this->formElements[$row['id']])) {
                    $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
                }
                if ($this->formElements[$row['id']]) {
                    $this->used_formElements[$row['id']]  = $this->formElements[$row['id']];
                    $this->used[$tracker->id][$row['id']] = $this->formElements[$row['id']];
                }
            }
        }
        return $this->used[$tracker->id];
    }
    
    /**
     * @param array the row allowing the construction of a Tracker_FormElement
     * @return Tracker_FormElement Object
     */
    public function getInstanceFromRow($row) {
        $instance = null;
        $klass = null;
        if (isset($this->classnames[$row['formElement_type']])) {
            $klass = $this->classnames[$row['formElement_type']];
        } else if (isset($this->special_classnames[$row['formElement_type']])) {
            $klass = $this->special_classnames[$row['formElement_type']];
        } else if (isset($this->group_classnames[$row['formElement_type']])) {
            $klass = $this->group_classnames[$row['formElement_type']];
        } else if (isset($this->staticfield_classnames[$row['formElement_type']])) {
            $klass = $this->staticfield_classnames[$row['formElement_type']];
        } 
        if ($klass) {            
            $instance = new $klass($row['id'], 
                                   $row['tracker_id'], 
                                   $row['parent_id'], 
                                   $row['name'], 
                                   $row['label'], 
                                   $row['description'], 
                                   $row['use_it'], 
                                   $row['scope'], 
                                   $row['required'],
                                   $row['notifications'],
                                   $row['rank']
            );
        } else {
            EventManager::instance()
                        ->processEvent('tracker_formElement_instance',
                                       array('instance' => &$instance,
                                             'type'     => $row['formElement_type'],
                                             'row'      => $row)
           );
        }
        return $instance;
    }
    
    /**
     * Creates a Tracker_FormElement Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported Tracker_FormElement
     * @param array            &$xmlMapping where the newly created formElements indexed by their XML IDs are stored
     * @param Tracker          $tracker     to which the tooltip is attached
     * 
     * @return Tracker_FormElement Object 
     */
    public function getInstanceFromXML($xml, &$xmlMapping) {
        $att = $xml->attributes();
        $row = array(
            'formElement_type' => (string)$att['type'],
            'name'             => (string)$xml->name,
            'label'            => (string)$xml->label, 
            'rank'             => (int)$att['rank'],
            'use_it'           => isset($att['use_it'])   ? (int)$att['use_it']        : 1,
            'scope'            => isset($att['scope'])    ? (string)$att['scope']         : 'P',
            'required'         => isset($att['required']) ? (int)$att['required'] : 0,
            'notifications'    => isset($att['notifications']) ? (int)$att['notifications'] : 0,
            'description'      => (string)$xml->description,
            'id'               => 0,
            'tracker_id'       => 0,
            'parent_id'        => 0,
        );
        $curElem = $this->getInstanceFromRow($row);
        $xmlMapping[(string)$xml['ID']] = $curElem;
        $curElem->continueGetInstanceFromXML($xml, $xmlMapping);
        return $curElem;
    }
    
    protected function getDao() {
        return new Tracker_FormElement_FieldDao();
    }
    /**
     * format a tracker field short name
     * @todo move this function in a utility class
     * @param string $label
     * @return string
     */
    public function deductNameFromLabel($label) {
        $normalizeChars = array(
            'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
            'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
            'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
            'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
            'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
        );
        $label = strtolower( trim($label) );
        $label = preg_replace('/[^\w\d_ -]/si', '', $label);
        //replace any space char with _
        $label = preg_replace('/\s+/', '_', $label);
        $label = strtr($label, $normalizeChars);
        return $label;
    }
    
    public function updateFormElement($formElement, $formElement_data) {
        $success = false;
        
        //check that the new name is not already used
        if (isset($formElement_data['name'])) {
            if (trim($formElement_data['name'])) {
                $formElement_data['name'] = $this->deductNameFromLabel($formElement_data['name']);
                if ($existing_field = $this->getFormElementByName($formElement->getTracker()->getId(), $formElement_data['name'])) {
                    if ($existing_field->getId() != $formElement->getId()) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_include_type', 'error_uniq_name'));
                        unset($formElement_data['name']);
                    }
                }
            } else {
                //Do not erase the field name
                unset($formElement_data['name']);
            }
        }
        
        $rank = isset($formElement_data['rank']) ? $formElement_data['rank'] : '--';
        //extract the parent_id from rank if needed
        //rank = <parent_id>:<rank> | <rank>
        $parent_id = isset($formElement_data['parent_id']) ? $formElement_data['parent_id'] : 0;
        if (strpos($rank, ':') !== false) {
            list($parent_id, $rank) = explode(':', $rank);
        }
        $formElement_data['parent_id'] = $parent_id;
        $formElement_data['rank']      = $rank;
        if ($formElement->updateProperties($formElement_data)) {
            $success = $this->getDao()->save($formElement);
        }
        return $success;
    }
   
    /**
     * Change formElement type 
     * @param formElement
     * @param type : the new formElement type
     * @return true on success
     */
    public function changeFormElementType($formElement, $type) {
        $success = false;
        if ($formElement->changeType($type)) {
            if ($this->getDao()->setType($formElement, $type)) {
                unset($this->formElements[$formElement->getId()]); //todo: clear other caches?
                $new_formelement = $this->getFormElementById($formElement->getId());
                $new_formelement->storeProperties($new_formelement->getFlattenPropertiesValues());
                $success = true;
            }
        }
        return $success;
    }



    /**
     * Unuse the formElement
     * @param boolean true if success
     */
    public function removeFormElement($formElement_id) {
        $success = false;
        if ($formElement = $this->getFormElementById($formElement_id)) {
            //Don't use anymore the field
            $formElement->use_it    = 0;
            //remove the field from its container
            $formElement->parent_id = 0; 
            $success = $this->getDao()->save($formElement);
        }
        return $success;
    }
    
    /**
     * Add the formElement
     * @param boolean true if success
     */
    public function addFormElement($formElement_id) {
        $success = false;
        if ($formElement = $this->getFormElementById($formElement_id)) {
            $formElement->use_it = 1;
            $formElement->rank   = 'beginning';
            
            if ($success = $this->getDao()->save($formElement)) {
                unset($this->formElements_by_parent[$formElement->parent_id]);
                //Set permissions if no permission set
                $perms = $formElement->getPermissions();
                //WARNING : here must be transformed the permissions array structure in order to pass it to the function that process form data permissions
                //see this::createFormElement to know how to convert permissions data
                if (empty($perms)) {
                    //Set default permissions
                    $permissions = array( $formElement_id => 
                         array(
                               $GLOBALS['UGROUP_ANONYMOUS']     => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_READ'),
                               $GLOBALS['UGROUP_REGISTERED']    => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_SUBMIT'),
                               $GLOBALS['UGROUP_PROJECT_MEMBERS']  => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_UPDATE')
                         )
                    );   
                    $tracker = $formElement->getTracker();
                    plugin_tracker_permission_process_update_fields_permissions(
                            $tracker->getGroupID(),
                            $tracker->getID(),
                            $this->getUsedFields($tracker),
                            $permissions
                    );
                }
            }
        }
        return $success;
    }
    
    /**
     * Delete permanently the formElement
     * @param boolean true if success
     */
    public function deleteFormElement($formElement_id) {
        $success = false;
        if ($formElement = $this->getFormElementById($formElement_id)) {
            //TODO: remove changeset values? or simply mark the formElement as deleted to be able to retrieve history?
            if ($success = $this->getDao()->delete($formElement)) {
                unset($this->formElements[$formElement_id]);
                unset($this->formElements_by_formElementcomponent[$formElement->parent_id]);
            }
        }
        return $success;
    }
    
    /**
     * Display the HTML for "field usage" admininistration
     *
     * @return void
     */
    public function displayFactories(Tracker $tracker) {
        $hp = Codendi_HTMLPurifier::instance();
        $klasses = $this->classnames;
        $special_klasses = $this->special_classnames;
        $all_klasses = array_merge($klasses, $special_klasses);
        EventManager::instance()->processEvent('tracker_formElement_classnames', 
                                               array('classnames' => &$all_klasses));
        $w = new Widget_Static($GLOBALS['Language']->getText('plugin_tracker_formelement_admin','fields'));
        $w->setContent($this->fetchFactoryButtons($klasses, $tracker));
        $w->display();
        
        $w = new Widget_Static($GLOBALS['Language']->getText('plugin_tracker_formelement_admin','dynamic_fields'));
        $w->setContent($this->fetchFactoryButtons($special_klasses, $tracker));
        $w->display();
        
        $w = new Widget_Static($GLOBALS['Language']->getText('plugin_tracker_formelement_admin','structural_elements'));
        $w->setContent($this->fetchFactoryButtons(array_merge($this->group_classnames, $this->staticfield_classnames), $tracker));
        $w->display();
    }
    
    protected function fetchFactoryButtons($klasses, $tracker) {
        $html = '';
        $html .= '<table class="tracker-admin-palette-content"><tr>';
        $i = 0;
        foreach($klasses as $type => $klass) {
            if (!($i % 2) && $i != 0) {
                $html .= '</tr><tr>';
            }
            $html .= '<td>';
            $html .= $this->getFactoryButton($klass, 'create-formElement['.  urlencode($type) .']', $tracker);
            $html .= '</td>';
            ++$i;
        }
        if ($i % 2) {
            $html .= '<td></td>';
        }
        $html .= '</tr></table>';
        return $html;
    }
    
    public function getFactoryButton($klass, $name, $tracker, $label = null, $description = null, $icon = null, $isUnique = null) {
        $hp = Codendi_HTMLPurifier::instance();
        //Waiting for PHP5.3 and $klass::staticMethod() and Late Static Binding
        $button = '';
        
        if (!$label) {
            eval("\$label = $klass::getFactoryLabel();");
        }
        if ($description === null) {
            eval("\$description = $klass::getFactoryDescription();");
        }
        if (!$icon) {
            eval("\$icon = $klass::getFactoryIconCreate();");
        }
        if ($isUnique === null) {
            eval("\$isUnique = $klass::getFactoryUniqueField();");
        }
        
        $button = '';
        if ($isUnique) {
            $type = array_search($klass, $this->classnames);
            $elements = $this->getFormElementsByType($tracker, $type);
            if (!empty($elements)) {
                
            }
        } else {        
            $button .= '<a class="button" name="'. $name .'" title="'. $hp->purify($description, CODENDI_PURIFIER_CONVERT_HTML) .'"><span>';
            $button .= '<img width="16" height="16" alt="" src="'. $icon .'" />';
            $button .=  $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML);
            $button .= '</span></a>';
        }
        return $button;
    }
    
    public function displayAdminCreateFormElement(TrackerManager $tracker_manager, $request, $current_user, $type) {
        if ($formElement = $this->getInstanceFromRow(array(
                                                    'formElement_type'  => $type,
                                                    'id'                => 0, 
                                                    'tracker_id'        => $request->get('tracker'), 
                                                    'parent_id'         => null, 
                                                    'name'              => null, 
                                                    'label'             => null, 
                                                    'description'       => null, 
                                                    'use_it'            => null, 
                                                    'scope'             => null, 
                                                    'rank'              => null,
                                                    'required'          => 0,
                                                    'notifications'     => 0,
        ))) {
            $klasses = array_merge($this->classnames, $this->special_classnames, $this->group_classnames, $this->staticfield_classnames);
            EventManager::instance()->processEvent('tracker_formElement_classnames', 
                                                   array('classnames' => &$klasses));
            $klass = $klasses[$type];
            //Waiting for PHP5.3 and $klass::staticMethod()
            $label = $description = '';
            eval("\$label = $klass::getFactoryLabel();");
            $formElement->displayAdminCreate($tracker_manager, $request, $current_user, $type, $label);
        }
    }
    
    public function createFormElement($tracker, $type, $formElement_data) {
        //Check that the label has been submitted
        if (isset($formElement_data['label']) && trim($formElement_data['label'])) {
            $label       = trim($formElement_data['label']);
            $description = isset($formElement_data['description'])?trim($formElement_data['description']):'';
            
            $rank = isset($formElement_data['rank']) ? $formElement_data['rank'] : 'end';
                
            //Check that the type is valid
            if (isset($this->classnames[$type]) 
                || isset($this->special_classnames[$type]) 
                || isset($this->group_classnames[$type]) 
                || isset($this->staticfield_classnames[$type])
            ) {
                //extract the parent_id from rank if needed
                //rank = <parent_id>:<rank> | <rank>
                $parent_id = isset($formElement_data['parent_id']) ? $formElement_data['parent_id'] : 0;
                if (strpos($rank, ':') !== false) {
                    list($parent_id, $rank) = explode(':', $rank);
                }
                
                //Check that parent_id is valid
                if($parent_id == 0 || $this->getFormElementById($parent_id)) {
                    
                    $name = null;
                    if (isset($formElement_data['name']) && trim($formElement_data['name'])) {
                        $name = $formElement_data['name'];
                    } else if ($label) {
                        $name = $this->deductNameFromLabel($label);
                    }
                    if ($name) {
                        $uniq = null;
                        while (!$uniq && $this->getFormElementByName($tracker->getId(), $name)) {
                            if ($uniq === null) {
                                $name .= '_';
                            }
                            $name .= '1';
                            $uniq = false;
                        }
                    }
                    
                    //Create the element
                    if($id = $this->getDao()->create($type,
                                                     $tracker->id, 
                                                     $parent_id,
                                                     $name,
                                                     $this->getPrefixNameForType($type),
                                                     $label,
                                                     $description,
                                                     $formElement_data['use_it'],
                                                     'P',
                                                     isset($formElement_data['required']) && $formElement_data['required'] ? 1 : 0,
                                                     isset($formElement_data['notifications']) && $formElement_data['notifications'] ? 1 : 0,
                                                     $rank)) {
                        //Set permissions
                        if (!array_key_exists($type, array_merge($this->group_classnames, $this->staticfield_classnames))) {
                            $ugroups_permissions = $this->getPermissionsFromFormElementData($id, $formElement_data);
                            if ($ugroups_permissions) {
                                plugin_tracker_permission_process_update_fields_permissions(
                                    $tracker->group_id,
                                    $tracker->id,
                                    $this->getFields($tracker),
                                    $ugroups_permissions
                                );
                            }
                        }
                        
                        //Announce to the world that an element has been created
                        EventManager::instance()->processEvent('tracker_formElement_justcreated', 
                                                               array('id' => $id,
                                                                     'row' => $formElement_data,
                                                                     'type' => $type));
                        //Clear some internal cache
                        unset($this->formElements_by_parent[$parent_id]);
                        
                        if ($formElement = $this->getFormElementById($id)) {
                            if (isset($formElement_data['specific_properties']) && is_array($formElement_data['specific_properties'])) {
                                $formElement->storeProperties($formElement_data['specific_properties']);
                            }
                            
                            //All is done, the field may want to do some things depending on the request
                            $formElement->afterCreate($formElement_data);
                            
                            return $id;
                        }
                    }
                } else {
                    //Parent doesn't exist
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Asked type is unknown');
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Label is needed !');
        }
        return false;
    }
    /**
     * This function process formelement data
     * @param Array $formElement_data
     */
    public function getPermissionsFromFormElementData($elmtId, $formElement_data) {
        //WARNING : READ/UPDATE is actual when last is READ, UPDATE liste (weird case, but good to know)        
        if (isset($formElement_data['permissions'])) {
            if ($ugroups_permissions = $formElement_data['permissions']) {
                foreach ($ugroups_permissions as $ugroup_id => $perms) {
                    $ugroups_permissions[$ugroup_id] = array();
                    foreach ($perms as $key => $value) {
                        $new_value = plugin_tracker_permission_get_input_value_from_permission($value);
                        $ugroups_permissions[$ugroup_id] = array_merge($ugroups_permissions[$ugroup_id], $new_value);
                    }
                }
                if ($ugroups_permissions) {
                    $ugroups_permissions = array($elmtId => $ugroups_permissions);
                }
            }
        } else {
            $ugroups_permissions = array($elmtId =>
                array(
                    $GLOBALS['UGROUP_ANONYMOUS'] => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_READ'),
                    $GLOBALS['UGROUP_REGISTERED'] => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_SUBMIT'),
                    $GLOBALS['UGROUP_PROJECT_MEMBERS'] => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_UPDATE')
                )
            );
        }
        return $ugroups_permissions;
    }

    /**
     * Creates new Tracker_Form element in the database
     * 
     * @param tracker $tracker of the created tracker
     * @param Object $formElement 
     * @param int $parent_id the id of the newly created parent formElement (0 when no parent)
     * 
     * @return the id of the newly created FormElement
     */
    public function saveObject($tracker, $formElement, $parent_id) {
        
        $properties = $formElement->getFlattenPropertiesValues();
        $formElement_data = array(  'name'          => $formElement->name,
                                    'label'         => $formElement->label,
                                    'parent_id'     => $parent_id,
                                    'description'   => $formElement->description,
                                    'label'         => $formElement->label,
                                    'use_it'        => $formElement->use_it,
                                    'scope'         => $formElement->scope,
                                    'required'      => $formElement->required,
                                    'notifications' => $formElement->notifications,
                                    'rank'          => $formElement->rank,
                                    'permissions'   => $formElement->getPermissions(),
                                    'specific_properties' => $properties);
        $type = $this->getType($formElement);
        
        if ($id = $this->createFormElement($tracker, $type, $formElement_data)) {
            $formElement->setId($id);
            $formElement->afterSaveObject($tracker);
        }
        return $id;
    }
    
    public function getGroupsByTrackerId($tracker_id) {
        $formElements = array();
        foreach($this->getDao()->searchByTrackerIdAndType($tracker_id, array_keys($this->group_classnames)) as $row) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            if ($this->formElements[$row['id']]) {
                $formElements[] = $this->formElements[$row['id']];
            }
        }
        return $formElements;
    }
    
    /**
     * Get the next used sibbling of an element.
     *
     * @param Tracker_FormElement $element
     *
     * @return Tracker_FormElement null if not found
     */
    public function getNextSibling($element) {
        $sibling = null;
        if ($row = $this->getDao()->searchNextUsedSibling($element->getTrackerId(), $element->getId())->getRow()) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            $sibling = $this->formElements[$row['id']];
        }
        return $sibling;
    }
    
    /**
     * Get the previous used sibbling of an element.
     *
     * @param Tracker_FormElement $element
     *
     * @return Tracker_FormElement null if not found
     */
    public function getPreviousSibling($element) {
        $sibling = null;
        if ($row = $this->getDao()->searchPreviousUsedSibling($element->getTrackerId(), $element->getId())->getRow()) {
            if (!isset($this->formElements[$row['id']])) {
                $this->formElements[$row['id']] = $this->getInstanceFromRow($row);
            }
            $sibling = $this->formElements[$row['id']];
        }
        return $sibling;
    }
}
?>
