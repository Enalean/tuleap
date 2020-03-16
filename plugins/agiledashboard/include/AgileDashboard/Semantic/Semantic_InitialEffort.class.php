<?php
/**
* Copyright Enalean (c) 2013-2016. All rights reserved.
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

class AgileDashBoard_Semantic_InitialEffort extends Tracker_Semantic
{
    public const NAME = 'initial_effort';

    /**
     * @var Tracker_FormElement_Field
     */
    protected $initial_effort_field;

    protected static $_instances;


    /**
     * Constructor
     *
     * @param Tracker                           $tracker    The tracker
     * @param Tracker_FormElement_Field $initial_effort_field The field
     */
    public function __construct(Tracker $tracker, ?Tracker_FormElement_Field $initial_effort_field = null)
    {
        parent::__construct($tracker);
        $this->initial_effort_field = $initial_effort_field;
    }

    /**
     * The short name of the semantic: initial_effort, plannedStoryPoints, ...
     *
     * @return string
     */
    public function getShortName()
    {
        return self::NAME;
    }

    /**
     * The label of the semantic: Initial Effort, Planned story points, ...
     *
     * @return string
     */
    public function getLabel()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_label');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_description');
    }

    /**
     * The Id of the (text) field used for initial_effort semantic
     *
     * @return int The Id of the (numeric) field used for initial_effort semantic, or 0 if no field
     */
    public function getFieldId()
    {
        if ($this->initial_effort_field) {
            return $this->initial_effort_field->getId();
        } else {
            return 0;
        }
    }

    /**
     * The (numeric) field used for initial_effort semantic
     *
     * @return Tracker_FormElement_Field The (numeric) field used for initial_effort semantic, or null if no field
     */
    public function getField()
    {
        return $this->initial_effort_field;
    }

    /**
     * Display the basic info about this semantic
     *
     * @return void
     */
    public function display()
    {
        echo $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_long_desc');

        if ($field = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId())) {
            $purifier = Codendi_HTMLPurifier::instance();
            echo $GLOBALS['Language']->getText(
                'plugin_agiledashboard_admin_semantic',
                'initial_effort_field',
                array($purifier->purify($field->getLabel()))
            );
        } else {
            echo $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_no_field');
        }
    }

    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $semantic_manager The semantic manager
     * @param TrackerManager          $tracker_manager  The tracker manager
     * @param Codendi_Request         $request          The request
     * @param PFUser                  $current_user     The user who made the request
     *
     * @return void
     */
    public function displayAdmin(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        $purify = Codendi_HTMLPurifier::instance();
        $semantic_manager->displaySemanticHeader($this, $tracker_manager);
        $html = '';

        if ($numeric_fields = Tracker_FormElementFactory::instance()->getUsedPotentiallyContainingNumericValueFields($this->tracker)) {
            $html .= '<form method="POST" action="' . $this->getUrl() . '">';
            $html .= $this->getCSRFToken()->fetchHTMLInput();
            $select = '<select name="initial_effort_field_id">';
            if (! $this->getFieldId()) {
                $select .= '<option value="-1" selected="selected">' . $purify->purify($GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'choose_a_field')) . '</option>';
            }

            foreach ($numeric_fields as $numeric_field) {
                if ($numeric_field->getId() == $this->getFieldId()) {
                    $selected = ' selected="selected" ';
                } else {
                    $selected = '';
                }
                $select .= '<option value="' . $purify->purify($numeric_field->getId()) . '" ' . $selected . '>' . $purify->purify($numeric_field->getLabel()) . '</option>';
            }
            $select .= '</select>';

            $unset_btn  = '<button type="submit" class="btn btn-danger" name="delete">';
            $unset_btn .= $purify->purify($GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'unset')) . '</button>';

            $submit_btn  = '<button type="submit" class="btn btn-primary" name="update">';
            $submit_btn .= $purify->purify($GLOBALS['Language']->getText('global', 'save_change')) . '</button>';

            if (! $this->getFieldId()) {
                $html .= $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_no_field');
                $html .= '<p>' . $purify->purify($GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'choose_one_advice'));
                $html .= $select . ' <br> ' . $submit_btn . '</p>';
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_field', array($select));
                $html .= $submit_btn . ' ' . $purify->purify($GLOBALS['Language']->getText('global', 'or')) . ' ' . $unset_btn;
            }
            $html .= '</form>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_impossible');
        }
        $html .= '<p><a href="' . TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId() . '&amp;func=admin-semantic">&laquo; ' . $purify->purify($GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'go_back_overview')) . '</a></p>';
        echo $html;

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    /**
     * Process the form
     *
     * @param Tracker_SemanticManager $semantic_manager The semantic manager
     * @param TrackerManager          $tracker_manager  The tracker manager
     * @param Codendi_Request         $request          The request
     * @param PFUser                  $current_user     The user who made the request
     *
     * @return void
     */
    public function process(Tracker_SemanticManager $semantic_manager, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        if ($request->exist('update')) {
            $this->getCSRFToken()->check();
            $field_id = $request->get('initial_effort_field_id');
            $field = Tracker_FormElementFactory::instance()->getUsedPotentiallyContainingNumericValueFieldById($this->tracker, $field_id);

            if ($field) {
                $this->initial_effort_field = $field;

                if ($this->save()) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'initial_effort_now', array($field->getLabel())));
                    $GLOBALS['Response']->redirect($this->getUrl());
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'unable_save_initial_effort'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'bad_field_initial_effort'));
            }
        } elseif ($request->exist('delete')) {
            $this->getCSRFToken()->check();
            if ($this->delete()) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'deleted_initial_effort'));
                $GLOBALS['Response']->redirect($this->getUrl());
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_agiledashboard_admin_semantic', 'unable_save_initial_effort'));
            }
        }

        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    public function save()
    {
        $dao = new AgileDashboard_Semantic_Dao_InitialEffortDao();
        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $dao = new AgileDashboard_Semantic_Dao_InitialEffortDao();
        return $dao->delete($this->tracker->getId());
    }

    /**
     * Load an instance of a AgileDashBoard_Semantic_InitialEffort
     *
     *
     * @return AgileDashBoard_Semantic_InitialEffort
     */
    public static function load(Tracker $tracker)
    {
        if (!isset(self::$_instances[$tracker->getId()])) {
            $field = self::getFieldFromTracker($tracker);
            self::$_instances[$tracker->getId()] = new AgileDashBoard_Semantic_InitialEffort($tracker, $field);
        }
        return self::$_instances[$tracker->getId()];
    }

    /**
     * @return Tracker_FormElement_Field | null
     */
    private static function getFieldFromTracker(Tracker $tracker)
    {
        $dao      = new AgileDashboard_Semantic_Dao_InitialEffortDao();
        $field    = null;
        $field_id = null;

        if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
            $field_id = $row['field_id'];
        }

        if ($field_id) {
            $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
        }

        return $field;
    }
    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root      the node to which the semantic is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if ($this->getFieldId() && in_array($this->getFieldId(), $xmlMapping)) {
            $child = $root->addChild('semantic');
            $child->addAttribute('type', $this->getShortName());
            $cdata = new XML_SimpleXMLCDATAFactory();
            $cdata->insert($child, 'shortname', $this->getShortName());
            $cdata->insert($child, 'label', $this->getLabel());
            $cdata->insert($child, 'description', $this->getDescription());
            $child->addChild('field')->addAttribute('REF', array_search($this->getFieldId(), $xmlMapping));
        }
    }

    /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     * @return bool returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics(Tracker_FormElement_Field $field)
    {
        return $this->getFieldId() == $field->getId();
    }
}
