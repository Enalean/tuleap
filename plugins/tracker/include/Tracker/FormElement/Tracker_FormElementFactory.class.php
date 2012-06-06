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

require_once('View/Admin/CreateVisitor.class.php');
require_once('View/Admin/CreateSharedVisitor.class.php');

require_once 'Tracker_FormElement_Field_Shareable.class.php';
require_once('Tracker_FormElement_Shared.class.php');
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
require_once('Tracker_FormElement_Field_Burndown.class.php');
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
        'shared'   => 'Tracker_FormElement_Shared',
    );
    
    protected $special_classnames     = array(
        'aid'      => 'Tracker_FormElement_Field_ArtifactId',
        'lud'      => 'Tracker_FormElement_Field_LastUpdateDate',
        'subby'    => 'Tracker_FormElement_Field_SubmittedBy',
        'subon'    => 'Tracker_FormElement_Field_SubmittedOn',
        'cross'    => 'Tracker_FormElement_Field_CrossReferences',
        'burndown' => 'Tracker_FormElement_Field_Burndown',
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
            $FormElementFactory_class = __CLASS__;
            self::setInstance(new $FormElementFactory_class);
        }
        return self::$_instance;
    }
    
    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     * 
     * @param Tracker_FormElementFactory $factory 
     */
    public static function setInstance(Tracker_FormElementFactory $factory) {
        self::$_instance = $factory;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     * 
     * @param Tracker_ArtifactFactory $factory 
     */
    public static function clearInstance() {
        self::$_instance = null;
    }
    
    /**
     * Returns the short name of the type of the given field
     * 
     * @param Tracker_FormElement $form_element
     * @return string 
     */
    public function getType($form_element) {
        $all_classnames = array_merge(
            $this->classnames,
            $this->special_classnames, 
            $this->group_classnames, 
            $this->staticfield_classnames
        );
        return array_search(get_class($form_element), $all_classnames);
    }
    
    /**
     * Return if type is a prefix
     *
     * @param the type of the field
     *
     * @return string the name
     */
    protected function isTypeAPrefix($type) {
        return (
            isset($this->group_classnames[$type]) 
            || isset($this->special_classnames[$type]) 
            || isset($this->staticfield_classnames[$type])
        );
    }
    
    /**
     * Get a formElement by id
     * @param int $id the id of the formElement to retrieve
     * @return Tracker_FormElement_Field
     */
    function getFormElementById($form_element_id) {
        if (!isset($this->formElements[$form_element_id])) {
            if ($row = $this->getDao()->searchById($form_element_id)->getRow()) {
                return $this->getCachedInstanceFromRow($row);
            }
            $this->formElements[$form_element_id] = null;
        }
        return $this->formElements[$form_element_id];
    }
    
    /**
     * Get a formElement by its short name
     *
     * @param int $tracker_id the tracker of the formElement to retrieve
     * @param string $name the name of the formElement to retrieve
     *
     * @return Tracker_FormElement_Field
     */
    function getFormElementByName($tracker_id, $name) {
        if (!isset($this->formElements_by_name[$tracker_id][$name])) {
            if ($row = $this->getDao()->searchByTrackerIdAndName($tracker_id, $name)->getRow()) {
                $this->formElements_by_name[$tracker_id][$name] = $this->getCachedInstanceFromRow($row);
            } else {
                $this->formElements_by_name[$tracker_id][$name] = null;
            }
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
            $form_element = $this->getFormElementById($id);
            if ($form_element && $form_element->isUsed()) {
                $this->used_formElements[$id] = $form_element;
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
        if ($row = $this->getDao()->searchUsedByTrackerIdAndName($tracker_id, $field_name)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }
    
    /**
     * Get the field by name if user is allowed to see it
     * 
     * @param int    $tracker_id
     * @param string $field_name
     * @param User   $user
     * 
     * @return Tracker_FormElement_Field
     */
    public function getUsedFieldByNameForUser($tracker_id, $field_name, User $user) {
        $field = $this->getUsedFieldByName($tracker_id, $field_name);
        if ($field && $field->userCanRead($user)) {
            return $field;
        }
        return null;
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
                $form_element = $this->getCachedInstanceFromRow($row);
                if ($form_element) {
                    $this->formElements_by_parent[$parent_id][$form_element->getId()] = $form_element;
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
        $all        = array();
        $tracker_id = $tracker->getId();
        foreach($this->getDao()->searchByTrackerId($tracker_id) as $row) {
            $form_element_id       = $row['id'];
            $all[$form_element_id] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($all);
    }
    
    /**
     * Get all formElements by parent id
     * @param int parent_id
     * @return array
     */
    public function getAllFormElementsByParentId($parent_id) {
        $all = array();
        foreach($this->getDao()->searchByParentId($parent_id) as $row) {
            $form_element_id = $row['id']; 
            $all[$form_element_id] = $this->getCachedInstanceFromRow($row);
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
     * @return Tracker_FormElement_Field_Shareable or null
     */
    public function getShareableFieldById($id) {
        $field = $this->getFieldById($id);
        if (is_a($field, 'Tracker_FormElement_Field_Shareable')) {
            return $field;
        }
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
        $tracker_id = $tracker->getId();
        if ($row = $this->getDao()->searchUsedByIdAndType($tracker_id, $field_id, $type)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
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
     * @return array of Tracker_FormElement_Field_ArtifactLink
     */
    public function getUsedArtifactLinkFields($tracker) {
        return $this->getUsedFormElementsByType($tracker, array('art_link'));
    }
    
    
    /**
     * @param Tracker $tracker
     *
     * @return array All lists formElements bind to users used by the tracker
     */
    public function getUsedUserListFields($tracker) {
        $form_elements = array();
        $tracker_id    = $tracker->getId();
        foreach($this->getDao()->searchUsedUserListFieldByTrackerId($tracker_id) as $row) {
            $form_elements[] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($form_elements);
    }
    
    public function getUsedUserListFieldById($tracker, $field_id) {
        $tracker_id = $tracker->getId();
        if ($row = $this->getDao()->getUsedUserListFieldById($tracker_id, $field_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
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
        $form_elements = array();
        foreach($this->getDao()->searchUsedUserSbFieldByTrackerId($tracker->getId()) as $row) {
            $form_elements[] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($form_elements);
    }
    
    public function getUsedUserSbFieldById($tracker, $field_id) {
        $tracker_id = $tracker->getId();
        if ($row = $this->getDao()->getUsedUserSbFieldById($tracker_id, $field_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
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
     * @param mixed   $type    The type (string) or types (array of) you are looking for
     * @param Boolean $used    Check if the type is used or not
     * 
     * @return Array of Tracker_FormElement All formElements used by the tracker
     */
    public function getFormElementsByType($tracker, $type, $used = null) {
        return $this->getCachedInstancesFromDAR($this->getDao()->searchUsedByTrackerIdAndType($tracker->id, $type, $used));
    }
    
    /**
     * @param DataAccessResult $dar the db collection of FormElements to instantiate
     * 
     * @return array All text formElements used by the tracker
     */
    protected function getCachedInstancesFromDAR(DataAccessResult $dar) {
        $form_elements = array();
        foreach($dar as $row) {
            $form_elements[] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($form_elements);
    }
    
    /**
     * @param Tracker $tracker
     * @param mixed $type the type (string) or types (array of) you are looking for
     * @return array All text formElements used by the tracker
     */
    public function getUsedFormElementsByType($tracker, $type) {
        $used                      = true;
        $tracker_id                = $tracker->getId();
        $used_form_elements_result = $this->getDao()->searchUsedByTrackerIdAndType($tracker_id, $type, $used);
        return $this->getCachedInstancesFromDAR($used_form_elements_result);
    }
    
    public function getUnusedFormElementForTracker(Tracker $tracker) {
        $unused = array();
        $tracker_id = $tracker->getId();
        foreach($this->getDao()->searchUnusedByTrackerId($tracker_id) as $row) {
            $form_element_id = $row['id']; 
            $unused[$form_element_id] = $this->getCachedInstanceFromRow($row);
        }
        return $unused;
    }
    
    public function getUsedFormElementForTracker($tracker) {
        $tracker_id = $tracker->getId();
        if (!isset($this->used[$tracker_id])) {
            $this->used[$tracker_id] = array();
            foreach($this->getDao()->searchUsedByTrackerId($tracker_id) as $row) {
                $form_element = $this->getCachedInstanceFromRow($row);
                if ($form_element) {
                    $form_element_id = $row['id'];
                    $this->used[$tracker_id][$form_element_id] = $form_element;
                    $this->used_formElements[$form_element_id] = $form_element;
                }
            }
        }
        return $this->used[$tracker_id];
    }
    
    protected function getFormElementClass($form_element_type) {
        $properties = array('classnames', 'special_classnames', 'group_classnames', 'staticfield_classnames');
        foreach ($properties as $class_names) {
            $classes = $this->$class_names;
            if (array_key_exists($form_element_type, $classes)) {
                return $classes[$form_element_type];
            }
        }
    }
    
    /**
     * @param array $row Raw data (typically from the db) of the form element
     *
     * @return Tracker_FormElement
     */
    private function getCachedInstanceFromRow($row) {
        $form_element_id = $row['id'];
        if (!isset($this->formElements[$form_element_id])) {
            $this->formElements[$form_element_id] = $this->getInstanceFromRow($row);
        }
        return $this->formElements[$form_element_id];
    }
    
    /**
     * @param array the row allowing the construction of a Tracker_FormElement
     * 
     * @return Tracker_FormElement Object
     */
    public function getInstanceFromRow($row) {
        $form_element      = null;
        $form_element_id   = $row['id'];
        $form_element_type = $row['formElement_type'];
        $klass             = $this->getFormElementClass($form_element_type);
        
        if ($klass) {
            $original_field = null;
            if ($row['original_field_id']) {
                $original_field = $this->getFormElementById($row['original_field_id']);
            }
            $form_element = new $klass(
                                    $row['id'], 
                                    $row['tracker_id'], 
                                    $row['parent_id'], 
                                    $row['name'], 
                                    $row['label'], 
                                    $row['description'], 
                                    $row['use_it'], 
                                    $row['scope'], 
                                    $row['required'],
                                    $row['notifications'],
                                    $row['rank'],
                                    $original_field
            );
        } else {
            $event_parameters = array(
                'instance' => &$form_element,
                'type'     => $form_element_type,
                'row'      => $row
            );
            EventManager::instance()->processEvent('tracker_formElement_instance', $event_parameters);
        }
         
        return $form_element;
    }
    
    /**
     * Create an array of FormElement based on a array of field definitions (database row for instance).
     * 
     * @param array $rows
     * 
     * @return Array of Tracker_FormElement
     */
    private function getCachedInstancesFromRows($rows) {
        $fields = array();
        foreach ($rows as $row) {
            $fields[] = $this->getCachedInstanceFromRow($row);
        }
        return $fields;
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
            'original_field_id'=> null,
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
    
    /**
     * Returns the FormElements that are a copy of given element
     * 
     * @param Tracker_FormElement $element
     * 
     * @return Array of Tracker_FormElement
     */
    public function getSharedTargets(Tracker_FormElement $element) {
        $dar = $this->getDao()->searchSharedTargets($element->getId());
        return $this->getCachedInstancesFromRows($dar);
    }
    
    /**
     * Returns the FormElements that are exported by this tracker
     * 
     * @return Array of Tracker_FormElement_Field
     */
    public function getProjectSharedFields(Project $project) {
        $dar = $this->getDao()->searchProjectSharedFieldsOriginals($project->getId());
        return $this->getCachedInstancesFromRows($dar);
    }
    
    /**
     * Fixes the original field id of the shared fields originating from the duplicated project
     * 
     * @param int $new_project_id
     * @param int $template_project_id
     * @param array $field_mapping 
     */
    public function fixOriginalFieldIdsAfterDuplication($new_project_id, $template_project_id, array $field_mapping) {
        $field_dao             = $this->getDao();
        $project_shared_fields = $field_dao->searchProjectSharedFieldsTargets($new_project_id);
        $template_field_ids    = $field_dao->searchFieldIdsByGroupId($template_project_id);
        
        foreach ($project_shared_fields as $project_shared_field) {
            if ($this->originalFieldIsInTemplateProject($project_shared_field, $template_field_ids)) {
                $id                = $project_shared_field['id'];
                $original_field_id = $project_shared_field['original_field_id'];
                
                $this->fixSharedFieldOriginalId($id, $original_field_id, $field_mapping);
                $this->fixSharedFieldOriginalValueIds($id, $original_field_id, $field_mapping);
            }
        }
    }
    
    private function fixSharedFieldOriginalId($field_id, $old_original_field_id, $field_mapping) {
        $new_original_field_id = $this->getNewOriginalFieldIdFromMapping($old_original_field_id, $field_mapping);
        $this->getDao()->updateOriginalFieldId($field_id, $new_original_field_id);
    }
    
    private function fixSharedFieldOriginalValueIds($field_id, $old_original_field_id, $field_mapping) {
        $field         = $this->getShareableFieldById($field_id);
        $value_mapping = $this->getValueMappingFromFieldMapping($old_original_field_id, $field_mapping);

        $field->fixOriginalValueIds($value_mapping);
    }
    
    private function getValueMappingFromFieldMapping($original_field_id, $field_mapping) {
        foreach($field_mapping as $row) {
            if($row['from'] == $original_field_id) {
                return $row['values'];
            }
        }
        return array();
    }
    
    private function getNewOriginalFieldIdFromMapping($old_field_id, array $field_mapping) {
        foreach($field_mapping as $row) {
            if($row['from'] == $old_field_id) {
                return $row['to'];
            }
        }
    }

    private function originalFieldIsInTemplateProject(array $shared_field, array $original_shared_field_ids) {
        return in_array($shared_field['original_field_id'], $original_shared_field_ids);
    }

    /**
     * Returns the shared field originals of which the user can either the orginal
     * or atleast one of the targets
     * 
     * @return Array of Tracker_FormElement_Field
     */
    public function getSharedFieldsReadableBy(User $user, Project $project) {
        $fields = $this->getProjectSharedFields($project);
        foreach ($fields as $k => $field) {
            if (!$this->userCanReadSharedField($user, $field)) {
                unset($fields[$k]);
            }
        }
        return $fields;
    }
    
    protected function userCanReadSharedField(User $user, Tracker_FormElement $field) {
        return ($field->userCanRead($user) && $this->canReadAllTargets($user, $field));
    }
    
    private function canReadAllTargets(User $user, Tracker_FormElement $field) {
        foreach ($this->getSharedTargets($field) as $target_field) {
            if (!$target_field->userCanRead($user)) {
                return false;
            }
        }
        return true;
    }
    
    public function updateFormElement($form_element, $form_element_data) {
        //check that the new name is not already used
        if (isset($form_element_data['name'])) {
            if (trim($form_element_data['name'])) {
                $form_element_data['name'] = $this->deductNameFromLabel($form_element_data['name']);
                if ($existing_field = $this->getFormElementByName($form_element->getTracker()->getId(), $form_element_data['name'])) {
                    if ($existing_field->getId() != $form_element->getId()) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_include_type', 'error_uniq_name'));
                        unset($form_element_data['name']);
                    }
                }
            } else {
                //Do not erase the field name
                unset($form_element_data['name']);
            }
        }
        
        $rank = isset($form_element_data['rank']) ? $form_element_data['rank'] : '--';
        //extract the parent_id from rank if needed
        //rank = <parent_id>:<rank> | <rank>
        $parent_id = isset($form_element_data['parent_id']) ? $form_element_data['parent_id'] : 0;
        if (strpos($rank, ':') !== false) {
            list($parent_id, $rank) = explode(':', $rank);
        }
        $form_element_data['parent_id'] = $parent_id;
        $form_element_data['rank']      = $rank;
        if ($form_element->updateProperties($form_element_data)) {
            if ($this->getDao()->save($form_element)) {
                return $this->getDao()->propagateUpdatedProperties($form_element);
            }
        }
        return false;
    }
   
    /**
     * Change formElement type 
     * @param formElement
     * @param type : the new formElement type
     * @return true on success
     */
    public function changeFormElementType($form_element, $type) {
        $success = false;
        if ($form_element->changeType($type)) {
            if ($this->getDao()->setType($form_element, $type)) {
                unset($this->formElements[$form_element->getId()]); //todo: clear other caches?
                $new_formelement = $this->getFormElementById($form_element->getId());
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
    public function removeFormElement($form_element_id) {
        $success = false;
        if ($form_element = $this->getFormElementById($form_element_id)) {
            //Don't use anymore the field
            $form_element->use_it    = 0;
            //remove the field from its container
            $form_element->parent_id = 0; 
            $success = $this->getDao()->save($form_element);
        }
        return $success;
    }
    
    /**
     * Add the formElement
     * @param boolean true if success
     */
    public function addFormElement($form_element_id) {
        $success = false;
        if ($form_element = $this->getFormElementById($form_element_id)) {
            $form_element->use_it = 1;
            $form_element->rank   = 'beginning';
            
            if ($success = $this->getDao()->save($form_element)) {
                unset($this->formElements_by_parent[$form_element->parent_id]);
                //Set permissions if no permission set
                $perms = $form_element->getPermissions();
                //WARNING : here must be transformed the permissions array structure in order to pass it to the function that process form data permissions
                //see this::createFormElement to know how to convert permissions data
                if (empty($perms)) {
                    //Set default permissions
                    $permissions = array( $form_element_id => 
                         array(
                               $GLOBALS['UGROUP_ANONYMOUS']     => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_READ'),
                               $GLOBALS['UGROUP_REGISTERED']    => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_SUBMIT'),
                               $GLOBALS['UGROUP_PROJECT_MEMBERS']  => plugin_tracker_permission_get_input_value_from_permission('PLUGIN_TRACKER_FIELD_UPDATE')
                         )
                    );   
                    $tracker = $form_element->getTracker();
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
    public function deleteFormElement($form_element_id) {
        $success = false;
        if ($form_element = $this->getFormElementById($form_element_id)) {
            //TODO: remove changeset values? or simply mark the formElement as deleted to be able to retrieve history?
            if ($success = $this->getDao()->delete($form_element)) {
                unset($this->formElements[$form_element_id]);
                unset($this->formElements_by_formElementcomponent[$form_element->parent_id]);
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
        $button_class = 'button';
        if (!$label) {
            eval("\$label = $klass::getFactoryLabel();");
        }
        if ($description === null) {
            eval("\$description = $klass::getFactoryDescription();");
        }
        if (!$icon) {
            eval("\$icon = $klass::getFactoryIconCreate();");
        }
        if ($this->isFieldUniqueAndAlreadyUsed($klass, $tracker, $isUnique)) {
            $button_class = 'button_disabled';
        }
        
        $button .= '<a class="'.$button_class.'" name="'. $name .'" title="'. $hp->purify($description, CODENDI_PURIFIER_CONVERT_HTML) .'"><span>';
        $button .= '<img width="16" height="16" alt="" src="'. $icon .'" />';
        $button .=  $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML);
        $button .= '</span></a>';
        
        return $button;
    }
    
    private function isFieldUniqueAndAlreadyUsed($klass, Tracker $tracker, $isUnique) {
        if ($isUnique === null) {
            eval("\$isUnique = $klass::getFactoryUniqueField();");
        }
        
        if ($isUnique) {
            $type = array_search($klass, $this->classnames);
            $used = true;
            $elements = $this->getFormElementsByType($tracker, $type, $used);
            if ($elements) {
                return true;
            }
        }
        return false;
    }
    
    public function displayAdminCreateFormElement(TrackerManager $tracker_manager, Codendi_Request $request, User $current_user, $type, Tracker $tracker) {
        $row = array(
                        'formElement_type'  => $type,
                        'id'                => 0, 
                        'tracker_id'        => $tracker->getId(), 
                        'parent_id'         => null, 
                        'name'              => null, 
                        'label'             => null, 
                        'description'       => null, 
                        'use_it'            => null, 
                        'scope'             => null, 
                        'rank'              => null,
                        'required'          => 0,
                        'notifications'     => 0,
                        'original_field_id' => null,
        );
        if ($form_element = $this->getInstanceFromRow($row)) {
            $form_element->setTracker($tracker);
            
            $klasses = array_merge($this->classnames, $this->special_classnames, $this->group_classnames, $this->staticfield_classnames);
            $this->getEventManager()->processEvent('tracker_formElement_classnames', 
                                                   array('classnames' => &$klasses));
            $klass = $klasses[$type];

            //Waiting for PHP5.3 and $klass::staticMethod()
            $getFactoryLabel = new ReflectionMethod($klass, 'getFactoryLabel');
            $label           = $getFactoryLabel->invoke(null);
            
            $allUsedElements = $this->getUsedFormElementForTracker($tracker);
            if ($form_element instanceof Tracker_FormElement_Shared) {
                $visitor = new Tracker_FormElement_View_Admin_CreateSharedVisitor($allUsedElements);
            } else {
                $visitor = new Tracker_FormElement_View_Admin_CreateVisitor($allUsedElements);
            }
            
            $visitor->setType($type);
	    $visitor->setLabel($label);

            $form_element->accept($visitor);  
            $visitor->display($tracker_manager, $request);
        }
    }
    
    /**
     * @return EventManager 
     */
    protected function getEventManager() {
        return EventManager::instance();
    }
    
    protected function typeIsValid($type) {
        return (
            isset($this->classnames[$type])
            || isset($this->special_classnames[$type])
            || isset($this->group_classnames[$type])
            || isset($this->staticfield_classnames[$type])
        );
    }
    
    public function createFormElement($tracker, $type, $form_element_data) {
        //Check that the label has been submitted
        if (isset($form_element_data['label']) && trim($form_element_data['label'])) {
            $label       = trim($form_element_data['label']);
            $description = isset($form_element_data['description']) ? trim($form_element_data['description']) : '';
            
            $rank = isset($form_element_data['rank']) ? $form_element_data['rank'] : 'end';
                
            //Check that the type is valid
            if ($this->typeIsValid($type)) {
                //extract the parent_id from rank if needed
                //rank = <parent_id>:<rank> | <rank>
                $parent_id = isset($form_element_data['parent_id']) ? $form_element_data['parent_id'] : 0;
                if (strpos($rank, ':') !== false) {
                    list($parent_id, $rank) = explode(':', $rank);
                }
                
                //Check that parent_id is valid
                if($parent_id == 0 || $this->getFormElementById($parent_id)) {
                    
                    $name = null;
                    if (isset($form_element_data['name']) && trim($form_element_data['name'])) {
                        $name = $form_element_data['name'];
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
                    
                    if ($this->isTypeAPrefix($type)) {
                        $prefix = $type;
                    } else {
                        $prefix = 'field_';
                    }
                    
                    $is_required       = isset($form_element_data['required']) ? (bool) $form_element_data['required'] : false;
                    $notify            = isset($form_element_data['notifications']) ? (bool) $form_element_data['notifications'] : false;
                    $original_field_id = isset($form_element_data['original_field_id']) ? $form_element_data['original_field_id'] : null;
                    
                    //Create the element
                    if($id = $this->getDao()->create($type,
                                                     $tracker->id, 
                                                     $parent_id,
                                                     $name,
                                                     $prefix,
                                                     $label,
                                                     $description,
                                                     $form_element_data['use_it'],
                                                     'P',
                                                     $is_required,
                                                     $notify,
                                                     $rank,
                                                     $original_field_id)) {
                        //Set permissions
                        if (!array_key_exists($type, array_merge($this->group_classnames, $this->staticfield_classnames))) {
                            $ugroups_permissions = $this->getPermissionsFromFormElementData($id, $form_element_data);
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
                                                                     'row' => $form_element_data,
                                                                     'type' => $type));
                        //Clear some internal cache
                        unset($this->formElements_by_parent[$parent_id]);
                        
                        if ($form_element = $this->getFormElementById($id)) {
                            if (isset($form_element_data['specific_properties']) && is_array($form_element_data['specific_properties'])) {
                                $form_element->storeProperties($form_element_data['specific_properties']);
                            }
                            
                            //All is done, the field may want to do some things depending on the request
                            $form_element->afterCreate($form_element_data);
                            
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
     * @param Array $form_element_data
     */
    public function getPermissionsFromFormElementData($elmtId, $form_element_data) {
        //WARNING : READ/UPDATE is actual when last is READ, UPDATE liste (weird case, but good to know)        
        if (isset($form_element_data['permissions'])) {
            if ($ugroups_permissions = $form_element_data['permissions']) {
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
     * @param Object $form_element 
     * @param int $parent_id the id of the newly created parent formElement (0 when no parent)
     *
     * @return the id of the newly created FormElement
     */
    public function saveObject($tracker, $form_element, $parent_id) {
        
        $properties = $form_element->getFlattenPropertiesValues();
        $form_element_data = array( 'name'          => $form_element->name,
                                    'label'         => $form_element->label,
                                    'parent_id'     => $parent_id,
                                    'description'   => $form_element->description,
                                    'label'         => $form_element->label,
                                    'use_it'        => $form_element->use_it,
                                    'scope'         => $form_element->scope,
                                    'required'      => $form_element->required,
                                    'notifications' => $form_element->notifications,
                                    'rank'          => $form_element->rank,
                                    'permissions'   => $form_element->getPermissions(),
                                    'specific_properties' => $properties
        );
        $type = $this->getType($form_element);
        
        if ($id = $this->createFormElement($tracker, $type, $form_element_data)) {
            $form_element->setId($id);
            $form_element->afterSaveObject($tracker);
        }
        return $id;
    }
    
    public function getGroupsByTrackerId($tracker_id) {
        $form_elements = array();
        foreach($this->getDao()->searchByTrackerIdAndType($tracker_id, array_keys($this->group_classnames)) as $row) {
            $form_elements[] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($form_elements);
    }
    
    /**
     * Get the next used sibbling of an element.
     *
     * @param Tracker_FormElement $element
     *
     * @return Tracker_FormElement null if not found
     */
    public function getNextSibling($element) {
        $tracker_id = $element->getTrackerId();
        $element_id = $element->getId();
        if ($row = $this->getDao()->searchNextUsedSibling($tracker_id, $element_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }
    
    /**
     * Get the previous used sibbling of an element.
     *
     * @param Tracker_FormElement $element
     *
     * @return Tracker_FormElement null if not found
     */
    public function getPreviousSibling($element) {
        $tracker_id = $element->getTrackerId();
        $element_id = $element->getId();
        if ($row = $this->getDao()->searchPreviousUsedSibling($tracker_id, $element_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }
    
    /**
     * @return Tracker_FormElement
     */
    public function getFieldFromTrackerAndSharedField(Tracker $tracker, Tracker_FormElement $shared) {
        $dar = $this->getDao()->searchFieldFromTrackerIdAndSharedFieldId($tracker->getId(), $shared->getId());
        return $this->getInstanceFromDar($dar);
        
    }

    public function getInstanceFromDar($dar) {
        if ($dar && $row = $dar->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }

    public function getArtifactLinkFieldsOfTrackers(array $trackers) {
        $art_link_field_ids = array();
        foreach ($trackers as $tracker) {
            $fields = $this->getUsedArtifactLinkFields($tracker);
            if (count($fields)) { 
                $art_link_field_ids[] = $fields[0]->getId();
            }
        }
        return $art_link_field_ids;
    }
    

}
?>
