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

require_once(dirname(__FILE__).'/../Tracker.class.php');
require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElementFactory.class.php');
require_once(dirname(__FILE__).'/../FormElement/Tracker_FormElement_Field_Text.class.php');
require_once('Tracker_Semantic.class.php');
require_once('dao/Tracker_Semantic_TitleDao.class.php');

class Tracker_Semantic_Title extends Tracker_Semantic {

    /**
     * @var Tracker_FormElement_Field_Text
     */
    protected $text_field;

    /**
     * Cosntructor
     *
     * @param Tracker                        $tracker    The tracker
     * @param Tracker_FormElement_Field_Text $text_field The field
     */
    public function __construct(Tracker $tracker, Tracker_FormElement_Field_Text $text_field = null) {
        parent::__construct($tracker);
        $this->text_field = $text_field;
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName() {
        return 'title';
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_label');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_description');
    }

    /**
     * The Id of the (text) field used for title semantic
     *
     * @return int The Id of the (text) field used for title semantic, or 0 if no field
     */
    public function getFieldId() {
        if ($this->text_field) {
            return $this->text_field->getId();
        } else {
            return 0;
        }
    }

    /**
     * The (text) field used for title semantic
     *
     * @return Tracker_FormElement_Field_Text The (text) field used for title semantic, or null if no field
     */
    public function getField() {
        return $this->text_field;
    }

    /**
     * Display the basic info about this semantic
     *
     * @return string html
     */
    public function display() {
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_long_desc');
        if ($field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId())) {
            echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_field', array($field->getLabel()));
        } else {
            echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_no_field');
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

        if ($text_fields = Tracker_FormElementFactory::instance()->getUsedTextFields($this->tracker)) {

            $html .= '<form method="POST" action="'. $this->geturl() .'">';
            $select = '<select name="text_field_id">';
            if ( ! $this->getFieldId()) {
                $select .= '<option value="-1" selected="selected">' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_a_field') . '</option>';
            }
            foreach ($text_fields as $text_field) {
                if ($text_field->getId() == $this->getFieldId()) {
                    $selected = ' selected="selected" ';
                } else {
                    $selected = '';
                }
                $select .= '<option value="' . $text_field->getId() . '" ' . $selected . '>' . $hp->purify($text_field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
            }
            $select .= '</select>';

            $submit = '<input type="submit" name="update" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';

            if (!$this->getFieldId()) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_no_field');
                $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_one_advice') . $select .' '. $submit .'</p>';
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_field', array($select)) . $submit;
            }
            $html .= '</form>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_impossible');
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
            if ($field = Tracker_FormElementFactory::instance()->getUsedTextFieldById($this->tracker, $request->get('text_field_id'))) {
                $this->text_field = $field;
                if ($this->save()) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_now', array($field->getLabel())));
                    $GLOBALS['Response']->redirect($this->getUrl());
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','unable_save_title'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','bad_field_title'));
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
        $dao = new Tracker_Semantic_TitleDao();
        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Title
     *
     * @param Tracker $tracker
     *
     * @return Tracker_Semantic_Title
     */
    public static function load(Tracker $tracker) {
        if (!isset(self::$_instances[$tracker->getId()])) {
            $field_id = null;
            $dao = new Tracker_Semantic_TitleDao();
            if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
                $field_id = $row['field_id'];
            }
            $field = null;
            if ($field_id) {
                $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
            }
            self::$_instances[$tracker->getId()] = new Tracker_Semantic_Title($tracker, $field);
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
     public function exportToXml(SimpleXMLElement $root, $xmlMapping) {
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
