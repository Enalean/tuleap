<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once(dirname(__FILE__) . '/../Semantic/Tracker_Semantic.class.php');
require_once(dirname(__FILE__) . '/../FormElement/Tracker_FormElementFactory.class.php');
require_once('dao/Tracker_TooltipDao.class.php');

class Tracker_Tooltip extends Tracker_Semantic
{

    public $fields = array();

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function getFields()
    {
        if (empty($this->fields)) {
            $tf = Tracker_FormElementFactory::instance();
            $this->fields = array();
            foreach ($this->getDao()->searchByTrackerId($this->tracker->id) as $row) {
                if ($field = $tf->getUsedFormElementById($row['field_id'])) {
                    $this->fields[$field->id] = $field;
                }
            }
        }
        return $this->fields;
    }

    private function getDao()
    {
        return new Tracker_TooltipDao();
    }

    /**
     * Save this semantic
     *
     * @return bool true if success, false otherwise
     */
    public function save()
    {
        $dao = $this->getDao();
        foreach ($this->fields as $fld) {
            $dao->add($this->tracker->id, $fld->id, 'end');
        }
        $this->fields = array();
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
    public function process(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        if ($request->get('add-field') && (int) $request->get('field')) {
            $this->getCSRFToken()->check();
            //retrieve the field if used
            $f = Tracker_FormElementFactory::instance()->getUsedFormElementById($request->get('field'));

            //store the new field
            $this->getDao()->add($this->tracker->id, $f->id, 'end');
        } elseif ((int) $request->get('remove')) {
            $this->getCSRFToken()->check();
            //retrieve the field if used
            $f = Tracker_FormElementFactory::instance()->getUsedFormElementById($request->get('remove'));

            //store the new field
            $this->getDao()->remove($this->tracker->id, $f->id);
        }
        $this->displayAdmin($sm, $tracker_manager, $request, $current_user);
    }

    /**
     * The short name of the semantic: tooltip, title, status, owner, ...
     *
     * @return string
     */
    public function getShortName()
    {
        return 'tooltip';
    }
    /**
     * The label of the semantic: Tooltip, ...
     *
     * @return string
     */
    public function getLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'tooltip_label');
    }

    /**
     * The description of the semantics. Used for breadcrumbs
     *
     * @return string
     */
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'tooltip_description');
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
    public function displayAdmin(Tracker_SemanticManager $sm, TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $sm->displaySemanticHeader($this, $tracker_manager);

        $html   = '';
        $fields = $this->getFields();
        if (!count($fields)) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'no_tooltip');
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'tooltip_fields');
            $html .= '<blockquote>';
            $html .= '<table>';
            foreach ($fields as $field) {
                $html .= '<tr><td>';
                $html .=  $hp->purify($field->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
                $html .= '</td><td>';
                $html .= '<form method="post" id="tracker-semantic-removal-action" action="' . $hp->purify($this->getUrl()) . '">';
                $html .= $this->getCSRFToken()->fetchHTMLInput();
                $html .= '<input type="hidden" name="remove" value="' . $hp->purify($field->getId()) .  '">';
                $html .= '<button type="submit" class="btn btn-link">';
                $html .= $GLOBALS['HTML']->getimage(
                    'ic/cross.png',
                    array(
                        'alt' => $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'tooltip_remove_field')
                    )
                );
                $html .= '</button>';
                $html .= '</form>';
                $html .= '</td></tr>';
            }
            $html .= '</table>';
            $html .= '</blockquote>';
        }
        $options = '';
        foreach ($this->tracker->getFormElements() as $formElement) {
            $options .= $formElement->fetchAddTooltip($fields);
        }
        if ($options) {
            $html .= '<form action="' . $this->getUrl() . '" method="POST">';
            $html .= $this->getCSRFToken()->fetchHTMLInput();
            $html .= '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'tooltip_add_field');
            $html .= '<select name="field">';
            $html .= $options;
            $html .= '</select>';
            $html .= '<input type="submit" name="add-field" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
            $html .= '</p>';
            $html .= '</form>';
        } else {
            $html .= '<em>' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'tooltip_no_more_field') . '</em>';
        }

        $html .= '<p><a href="' . TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId() . '&amp;func=admin-semantic">&laquo; ' . $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'go_back_overview') . '</a></p>';
        echo $html;
        $sm->displaySemanticFooter($this, $tracker_manager);
    }

    /**
     * Display the basic info about this semantic
     *
     * @return void
     */
    public function display()
    {
        $html   = '';
        $hp     = Codendi_HTMLPurifier::instance();
        $fields = $this->getFields();
        $html .= '<p>';
        if (!count($fields)) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'no_tooltip');
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'tooltip_fields');
            $html .= '<ul>';
            foreach ($fields as $f) {
                $html .=  '<li><strong>' . $hp->purify($f->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</strong></li>';
            }
            $html .= '</ul>';
        }
        $html .= '</p>';
        echo $html;
    }

    /**
     * Transforms tooltip into a SimpleXMLElement
     *
     * @param SimpleXMLElement &$root         the node to which the semantic is attached (passed by reference)
     * @param array            $xmlMapping  correspondance between real field ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $child = $root->addChild('semantic');
        $child->addAttribute('type', $this->getShortName());
        foreach ($this->getFields() as $field) {
            $child->addChild('field')->addAttribute('REF', array_search($field->id, $xmlMapping));
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
        $fields = $this->getFields();
        foreach ($fields as $f) {
            if ($f->getId() == $field->getId()) {
                return true;
            }
        }
        return false;
    }
}
