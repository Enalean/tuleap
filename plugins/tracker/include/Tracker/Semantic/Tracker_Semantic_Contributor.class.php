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
 

class Tracker_Semantic_Contributor extends Tracker_Semantic {
    
    /**
     * @var Tracker_FormElement_Field_List
     */
    protected $list_field;
    
    /**
     * Cosntructor
     *
     * @param Tracker                        $tracker    The tracker
     * @param Tracker_FormElement_Field_List $list_field The field
     */
    public function __construct(Tracker $tracker, Tracker_FormElement_Field_List $list_field = null) {
        parent::__construct($tracker);
        $this->list_field = $list_field;
    }
    
    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName() {
        return 'contributor';
    }
    
    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_label');
    }
    
    /**
     * The description of the semantics. Used for breadcrumbs
     * 
     * @return string
     */
    public function getDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_description');
    }
    
    /**
     * The Id of the (list) field used for contributor semantic
     * 
     * @return int The Id of the (list) field used for contributor semantic, or 0 if no field
     */
    public function getFieldId() {
        if ($this->list_field) {
            return $this->list_field->getId();
        } else {
            return 0;
        }
    }
    
    /**
     * The (list) field used for contributor semantic
     * 
     * @return Tracker_FormElement_Field_List The (list) field used for contributor semantic, or null if no field
     */
    public function getField() {
        return $this->list_field;
    }
    
    /**
     * Display the basic info about this semantic
     *
     * @return string html
     */
    public function display() {
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_long_desc');
        if ($field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId())) {
            echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_field', array($field->getLabel()));
        } else {
            echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_no_field');
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

        if ($list_fields = Tracker_FormElementFactory::instance()->getUsedUserSbFields($this->tracker)) {
            
            $html .= '<form method="POST" action="'. $this->geturl() .'">';
            $select = '<select name="list_field_id">';
            if ( ! $this->getFieldId()) {
                $select .= '<option value="-1" selected="selected">' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_a_field') . '</option>';
            }
            foreach ($list_fields as $list_field) {
                if ($list_field->getId() == $this->getFieldId()) {
                    $selected = ' selected="selected" ';
                } else {
                    $selected = '';
                }
                $select .= '<option value="' . $list_field->getId() . '" ' . $selected . '>' . $hp->purify($list_field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
            }
            $select .= '</select>';
            
            $submit = '<input type="submit" name="update" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            
            if (!$this->getFieldId()) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_no_field');
                $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_one_advice') . $select .' '. $submit .'</p>'; 
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_field', array($select)) . $submit;
            }
            $html .= '</form>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_impossible'); 
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
            if ($field = Tracker_FormElementFactory::instance()->getUsedUserSbFieldById($this->tracker, $request->get('list_field_id'))) {
                $this->list_field = $field;
                if ($this->save()) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','contributor_now', array($field->getLabel())));
                    $GLOBALS['Response']->redirect($this->getUrl());
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','unable_save_contributor'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','bad_field_contributor'));
            }
        }
        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }
    
    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    public function save() {
        $dao = new Tracker_Semantic_ContributorDao();
        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }
    
    /**
     * Load an instance of a Tracker_Semantic_Contributor
     *
     * @param Tracker $tracker
     *
     * @return Tracker_Semantic_Contributor
     */
    public static function load(Tracker $tracker) {
        $field_id = null;
        $dao = new Tracker_Semantic_ContributorDao();
        if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
            $field_id = $row['field_id'];
        }
        $field = null;
        if ($field_id) {
            $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
        }
        return new Tracker_Semantic_Contributor($tracker, $field);
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
