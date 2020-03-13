<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


class Tracker_Semantic_Description extends Tracker_Semantic
{
    public const NAME = 'description';

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
    public function __construct(Tracker $tracker, ?Tracker_FormElement_Field_Text $text_field = null)
    {
        parent::__construct($tracker);
        $this->text_field = $text_field;
    }

    /**
     * The short name of the semantic: tooltip, description, status, owner, ...
     *
     * @return string
     */
    public function getShortName()
    {
        return self::NAME;
    }

    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_label');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_description');
    }

    /**
     * The Id of the (text) field used for description semantic
     *
     * @return int The Id of the (text) field used for description semantic, or 0 if no field
     */
    public function getFieldId()
    {
        if ($this->text_field) {
            return $this->text_field->getId();
        } else {
            return 0;
        }
    }

    /**
     * The (text) field used for description semantic
     *
     * @return Tracker_FormElement_Field_Text The (text) field used for description semantic, or null if no field
     */
    public function getField()
    {
        return $this->text_field;
    }

    /**
     * Display the basic info about this semantic
     *
     * @return void
     */
    public function display()
    {
        $warning = '';
        $field   = Tracker_FormElementFactory::instance()->getUsedFormElementById($this->getFieldId());

        if ($field) {
            $purifier = Codendi_HTMLPurifier::instance();
            $content  =  $GLOBALS['Language']->getText(
                'plugin_tracker_admin_semantic',
                'description_field',
                array($purifier->purify($field->getLabel()))
            );

            if (Tracker_FormElementFactory::instance()->getUsedFieldByIdAndType($this->tracker, $field->getId(), array('string', 'ref'))) {
                $warning = '<p class="alert alert-warning">' .
                            $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_warning') .
                            '</p>';
            }
        } else {
            $content = $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_no_field');
        }

        echo $warning;
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_long_desc');
        echo $content;
    }

    /**
     * Display the form to let the admin change the semantic
     *
     * @param Tracker_SemanticManager $sm              The semantic manager
     * @param TrackerManager          $tracker_manager The tracker manager
     * @param Codendi_Request         $request         The request
     * @param PFUser                    $current_user    The user who made the request
     *
     * @return void
     */
    public function displayAdmin(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        $hp = Codendi_HTMLPurifier::instance();
        $sm->displaySemanticHeader($this, $tracker_manager);
        $html = '';

        $text_fields = Tracker_FormElementFactory::instance()->getUsedFormElementsByType($this->tracker, array('text'));

        if ($text_fields) {
            $html .= '<form method="POST" action="' . $this->getUrl() . '">';
            $html .= $this->getCSRFToken()->fetchHTMLInput();
            $select = '<select name="text_field_id">';
            if (! $this->getFieldId()) {
                $select .= '<option value="-1" selected="selected">' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'choose_a_field') . '</option>';
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

            $unset_btn  = '<button type="submit" class="btn btn-danger" name="delete">';
            $unset_btn .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'unset') . '</button>';

            $submit_btn  = '<button type="submit" class="btn btn-primary" name="update">';
            $submit_btn .= $GLOBALS['Language']->getText('global', 'save_change') . '</button>';

            if (!$this->getFieldId()) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_no_field');
                $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'choose_one_advice') . ' ';
                $html .= $select . ' <br> ' . $submit_btn;
                $html .= '</p>';
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_field', $select);
                $html .= $submit_btn . ' ' . $GLOBALS['Language']->getText('global', 'or') . ' ' . $unset_btn;
            }
            $html .= '</form>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_impossible');
        }
        $html .= '<p><a href="' . TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId() . '&amp;func=admin-semantic">&laquo; ' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'go_back_overview') . '</a></p>';
        echo $html;
        $sm->displaySemanticFooter($this, $tracker_manager);
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
    public function process(
        Tracker_SemanticManager $sm,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        if ($request->exist('update')) {
            $this->getCSRFToken()->check();
            if ($field = Tracker_FormElementFactory::instance()->getUsedFieldByIdAndType($this->tracker, $request->get('text_field_id'), array('text'))) {
                $this->text_field = $field;
                if ($this->save()) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_now', array($field->getLabel())));
                    $GLOBALS['Response']->redirect($this->getUrl());
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'unable_save_description'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'bad_field_description'));
            }
        } elseif ($request->exist('delete')) {
            $this->getCSRFToken()->check();
            if ($this->delete()) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'deleted_description'));
                $GLOBALS['Response']->redirect($this->getUrl());
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'unable_save_description'));
            }
        }
        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }

    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    public function save()
    {
        $dao = new Tracker_Semantic_DescriptionDao();
        return $dao->save($this->tracker->getId(), $this->getFieldId());
    }

    public function delete()
    {
        $dao = new Tracker_Semantic_DescriptionDao();
        return $dao->delete($this->tracker->getId());
    }

    protected static $_instances;
    /**
     * Load an instance of a Tracker_Semantic_Description
     *
     *
     * @return Tracker_Semantic_Description
     */
    public static function load(Tracker $tracker)
    {
        if (!isset(self::$_instances[$tracker->getId()])) {
            $field_id = null;
            $dao = new Tracker_Semantic_DescriptionDao();
            if ($row = $dao->searchByTrackerId($tracker->getId())->getRow()) {
                $field_id = $row['field_id'];
            }
            $field = null;
            if ($field_id) {
                $field = Tracker_FormElementFactory::instance()->getFieldById($field_id);
            }
            self::$_instances[$tracker->getId()] = new Tracker_Semantic_Description($tracker, $field);
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
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if ($this->getFieldId() && in_array($this->getFieldId(), $xmlMapping)) {
            $child = $root->addChild('semantic');
            $child->addAttribute('type', $this->getShortName());
            $cdata = new \XML_SimpleXMLCDATAFactory();
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
     *
     * @return bool returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics(Tracker_FormElement_Field $field)
    {
        return $this->getFieldId() == $field->getId();
    }

    /**
     * Allows to inject a fake Semantic for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function setInstance(Tracker_Semantic_Description $description, Tracker $tracker)
    {
        self::$_instances[$tracker->getId()] = $description;
    }

    /**
     * Allows to clear Semantics for tests. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstances()
    {
        self::$_instances = null;
    }
}
