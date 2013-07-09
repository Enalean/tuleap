<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class AgileDashBoard_Semantic_InitialEffort extends Tracker_Semantic {
    const NAME = 'initialEffort';

    /**
     * @var Tracker_FormElement_Field_Numeric
     */
    protected $numeric_field;

    /**
     * Constructor
     *
     * @param Tracker                           $tracker    The tracker
     * @param Tracker_FormElement_Field_Numeric $numeric_field The field
     */
    public function __construct(Tracker $tracker, Tracker_FormElement_Field_Numeric $numeric_field = null) {
        parent::__construct($tracker);
        $this->numeric_field = $numeric_field;
    }

    /**
     * The short name of the semantic: initialEffort, plannedStoryPoints, ...
     *
     * @return string
     */
    public function getShortName() {
        return self::NAME;
    }

    /**
     * The label of the semantic: Initial Effort, Planned story points, ...
     *
     * @return string
     */
    public function getLabel() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic','initial_effort_label');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic','initial_effort_description');
    }

    /**
     * The Id of the (text) field used for initialEffort semantic
     *
     * @return int The Id of the (numeric) field used for initialEffort semantic, or 0 if no field
     */
    public function getFieldId() {
        if ($this->numeric_field) {
            return $this->numeric_field->getId();
        } else {
            return 0;
        }
    }

    /**
     * The (numeric) field used for initialEffort semantic
     *
     * @return Tracker_FormElement_Field_Text The (numeric) field used for initialEffort semantic, or null if no field
     */
    public function getField() {
        return $this->numeric_field;
    }

    /**
     * Display the basic info about this semantic
     *
     * @return string html
     */
    public function display() {
        echo $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic','initial_effort_long_desc');
        if ($field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId())) {
            echo $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic','initial_effort_field', array($field->getLabel()));
        } else {
            echo $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic','initial_effort_no_field');
        }
    }

    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param PFUser                    $current_user    The user who made the request
     *
     * @return string html
     */
    public function displayAdmin(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user) {
//        $hp = Codendi_HTMLPurifier::instance();
//        $sm->displaySemanticHeader($this, $tracker_manager);
//        $html = '';
//
//        if ($text_fields = Tracker_FormElementFactory::instance()->getUsedTextFields($this->tracker)) {
//
//            $html .= '<form method="POST" action="'. $this->geturl() .'">';
//            $select = '<select name="text_field_id">';
//            if ( ! $this->getFieldId()) {
//                $select .= '<option value="-1" selected="selected">' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_a_field') . '</option>';
//            }
//            foreach ($text_fields as $text_field) {
//                if ($text_field->getId() == $this->getFieldId()) {
//                    $selected = ' selected="selected" ';
//                } else {
//                    $selected = '';
//                }
//                $select .= '<option value="' . $text_field->getId() . '" ' . $selected . '>' . $hp->purify($text_field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
//            }
//            $select .= '</select>';
//
//            $submit = '<input type="submit" name="update" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
//
//            if (!$this->getFieldId()) {
//                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_no_field');
//                $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','choose_one_advice') . $select .' '. $submit .'</p>';
//            } else {
//                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_field', array($select)) . $submit;
//            }
//            $html .= '</form>';
//        } else {
//            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_impossible');
//        }
//        $html .= '<p><a href="'.TRACKER_BASE_URL.'/?tracker='. $this->tracker->getId() .'&amp;func=admin-semantic">&laquo; ' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','go_back_overview') . '</a></p>';
//        echo $html;
//        $sm->displaySemanticFooter($this, $tracker_manager);
    }

    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param PFUser                    $current_user    The user who made the request
     *
     * @return void
     */
    public function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user) {
//        if ($request->exist('update')) {
//            if ($field = Tracker_FormElementFactory::instance()->getUsedTextFieldById($this->tracker, $request->get('text_field_id'))) {
//                $this->numeric_field = $field;
//                if ($this->save()) {
//                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','title_now', array($field->getLabel())));
//                    $GLOBALS['Response']->redirect($this->getUrl());
//                } else {
//                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','unable_save_title'));
//                }
//            } else {
//                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','bad_field_title'));
//            }
//        }
//        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }

    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    public function save() {
//        $dao = new Tracker_Semantic_InitialEffortDao();
//        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_InitialEffort
     *
     * @param Tracker $tracker
     * @return Tracker_Semantic_InitialEffort
     */
    public static function load(Tracker $tracker) {
        if (!isset(self::$_instances[$tracker->getId()])) {
            $field_id = null;
//            $dao = new Tracker_Semantic_InitialEffortDao();
//            if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
//                $field_id = $row['field_id'];
//            }
            $field = null;
            if ($field_id) {
                $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
            }
            self::$_instances[$tracker->getId()] = new AgileDashBoard_Semantic_InitialEffort($tracker, $field);
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
     }

    /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     * @return boolean returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics($field) {
        return $this->getFieldId() == $field->getId();
    }
}
?>
