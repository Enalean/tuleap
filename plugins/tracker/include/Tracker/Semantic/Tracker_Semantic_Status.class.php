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
require_once('Tracker_Semantic.class.php');
require_once(dirname(__FILE__).'/../TrackerManager.class.php');
require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElement_Field_List.class.php');
require_once('common/include/Codendi_Request.class.php');
require_once('common/user/User.class.php');
require_once('dao/Tracker_Semantic_StatusDao.class.php');

class Tracker_Semantic_Status extends Tracker_Semantic {
    
    /**
     * @var Tracker_FormElement_Field_List
     */
    protected $list_field;
    
    /**
     * @var array
     */
    protected $open_values;

    /**
     * Constructor
     *
     * @param Tracker                        $tracker     The tracker
     * @param Tracker_FormElement_Field_List $list_field  The field
     * @param array                          $open_values The values with the meaning "Open"
     */
    public function __construct(Tracker $tracker, Tracker_FormElement_Field_List $list_field = null, $open_values = array()) {
        parent::__construct($tracker);
        $this->list_field  = $list_field;
        $this->open_values = $open_values;
    }
    
    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName() {
        return 'status';
    }
    
    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_label');
    }
    
    /**
     * The description of the semantics. Used for breadcrumbs
     * 
     * @return string
     */
    public function getDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_description');
    }
    
    /**
     * The Id of the (SB) field used for status semantic, or 0 if no field
     * 
     * @return int The Id of the (SB) field used for status semantic, or 0 if no field
     */
    public function getFieldId() {
        if ($this->list_field) {
            return $this->list_field->getId();
        } else {
            return 0;
        }
    }
    
    /**
     * The (list) field used for status semantic
     * 
     * @return Tracker_FormElement_Field_List The (list) field used for status semantic, or null if no field
     */
    public function getField() {
        return $this->list_field;
    }
    
    /**
     * The Ids of open values for this status semantic
     * 
     * @return array of int The Id of the open values for this status semantic
     */
    public function getOpenValues() {
        return $this->open_values;
    }
    
    /**
     * Display the basic info about this semantic
     *
     * @return string html
     */
    public function display() {
        if ($this->list_field) {
            echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_long_desc', array($this->list_field->getLabel()));
            if ($this->open_values) {
                echo '<ul>';
                $field_values = $this->list_field->getAllValues();
                foreach ($this->open_values as $v) {
                    if (isset($field_values[$v])) {
                        echo '<li><strong>'. $field_values[$v]->getLabel() .'</strong></li>';
                    }
                }
                echo '</ul>';
            } else {
                echo '<blockquote><em>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_no_value') . '</em></blockquote>';
            }
        } else {
            echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_no_field');
        }
    }
    
    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param User                    $current_user    The user who made the request
     *
     * @return string html
     */
    public function displayAdmin(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, User $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $sm->displaySemanticHeader($this, $tracker_manager);
        $html = '';
        
        if ($list_fields = Tracker_FormElementFactory::instance()->getUsedListFields($this->tracker)) {
            
            $html .= '<form method="POST" action="'. $this->geturl() .'">';
            
            // field selectbox
            $field = null;
            $select = '<select name="field_id">';

            $selected = '';
            if ( ! $this->list_field) {
                $selected = 'selected="selected"';
            }
            $select .= '<option value="-1" '. $selected .'>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_a_field') . '</option>';

            foreach ($list_fields as $list_field) {
                $selected = '';
                if ($list_field->getId() == $this->getFieldId()) {
                    $field = $list_field;
                    $selected = ' selected="selected" ';
                }
                $select .= '<option value="' . $list_field->getId() . '" ' . $selected . '>' . $hp->purify($list_field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
            }
            $select .= '</select>';
            
            // open values selectbox
            $params = '';
            if ($field) {
                $params = 'name="open_values['. $this->getFieldId() .'][]" multiple="multiple" size="7" style="vertical-align:top;"';
            }
            $values = '<select '. $params .'>';
            if ($field) { //see above
                foreach ($field->getAllValues() as $v) {
                    if (!$v->isHidden()) {
                        $selected = '';
                        if (in_array($v->getId(), $this->open_values)) {
                            $selected = ' selected="selected" ';
                        }
                        $values .= '<option value="' . $v->getId() . '" ' . $selected . '>' . $hp->purify($v->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
                    }
                }
            }
            $values .= '</select>';
            
            // submit button
            $submit = '<input type="submit" name="update" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            
            if (!$this->getFieldId()) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_no_field');
                $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_one_advice') . $select .' '. $submit .'</p>';
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_long_desc', array($select)) . $values .' '. $submit;
            }
            $html .= '</form>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_impossible');
        }
        $html .= '<p><a href="'.TRACKER_BASE_URL.'/?tracker='. $this->tracker->getId() .'&amp;func=admin-semantic">&laquo; ' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','go_back_overview') . '</a></p>';
        echo $html;
        $sm->displaySemanticFooter($this, $tracker_manager);
    }
    
    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param User                    $current_user    The user who made the request
     *
     * @return void
     */
    public function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, User $current_user) {
        if ($request->exist('update')) {
            if ($request->get('field_id') == '-1') {
                if ($this->getField()) {
                    $this->delete();
                }
            } else if ($field = Tracker_FormElementFactory::instance()->getUsedListFieldById($this->tracker, $request->get('field_id'))) {
                if ($this->getFieldId() != $request->get('field_id') || $request->get('open_values')) {
                    $this->list_field = $field;
                    $open_values = $request->get('open_values');
                    if ($open_values && is_array($open_values) && isset($open_values[$this->getFieldId()]) && is_array($open_values[$this->getFieldId()])) {
                        $this->open_values = $open_values[$this->getFieldId()];
                        if ($this->save()) {
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','status_now', array($field->getLabel())));
                            $GLOBALS['Response']->redirect($this->getUrl());
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','unable_save_status'));
                        }
                    } else {
                        //Display the form to choose the values
                        //nop - see below
                    }
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','bad_field_status'));
            }
        }
        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }
    
    /**
     * Delete this semantic
     */
    private function delete() {
        $this->list_field  = null;
        $this->open_values = array();
        $dao = new Tracker_Semantic_StatusDao();
        $dao->delete($this->tracker->getId());
    }
    
    /**
     * Save this semantic
     *
     * @return bool 
     */
    public function save() {
        $dao = new Tracker_Semantic_StatusDao();
        $open_values = array();
        foreach($this->open_values as $v) {
            if (is_scalar($v)) {
                $open_values[] = $v;
            } else {
                $open_values[] = $v->getId();
            }
        }
        $this->open_values = $open_values;
        return $dao->save($this->tracker->getId(), $this->getFieldId(), $this->open_values);
    }
    
    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Status
     *
     * @param Tracker $tracker the tracker
     *
     * @return Tracker_Semantic_Status
     */
    public static function load(Tracker $tracker) {
        if (!isset(self::$_instances[$tracker->getId()])) {
            $field_id = null;
            $open_values = array();
            $dao = new Tracker_Semantic_StatusDao();
            foreach ($dao->searchByTrackerId($tracker->getId()) as $row) {
                $field_id      = $row['field_id'];
                $open_values[] = $row['open_value_id'];
            }
            if (!$open_values) {
                $open_values[] = 100;
            }
            $fef = Tracker_FormElementFactory::instance();
            $field = $fef->getFieldById($field_id);
            self::$_instances[$tracker->getId()] = new Tracker_Semantic_Status($tracker, $field, $open_values);
        }
        return self::$_instances[$tracker->getId()];
    }
    
    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root      the node to which the semantic is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
     public function exportToXML(&$root, $xmlMapping) {
         if ($this->getFieldId()) {
             $child = $root->addChild('semantic');
             $child->addAttribute('type', $this->getShortName());
             $child->addChild('shortname', $this->getShortName());
             $child->addChild('label', $this->getLabel());
             $child->addChild('description', $this->getDescription());
             $child->addChild('field')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
             $node_open_values = $child->addChild('open_values');
             foreach ($this->open_values as $value) {
                 if ($ref = array_search($value, $xmlMapping['values'])) {
                     $node_open_values->addChild('open_value')->addAttribute('REF', $ref);
                 }
             }
         }
     }
     
     /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     *
     * @return boolean returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics($field) {
        return $this->getFieldId() == $field->getId();
    }
    
}
?>
