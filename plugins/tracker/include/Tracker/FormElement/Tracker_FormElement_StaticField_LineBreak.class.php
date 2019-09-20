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


class Tracker_FormElement_StaticField_LineBreak extends Tracker_FormElement_StaticField
{

    protected function fetchReadOnly()
    {
        $html = '';
        $html .= '<br class="tracker-admin-linebreak" id="tracker-admin-formElements_'. $this->id .'" />';
        return $html;
    }

    public function fetchAdmin($tracker)
    {
        $html = '';
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<div class="tracker-admin-field" id="tracker-admin-formElements_'. $this->id .'">';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</span>';
        $html .= '<div class="tracker-admin-field-controls">';
        $html .= '<a class="edit-field" href="'. $this->getAdminEditUrl() .'">'. $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => 'edit')) .'</a> ';
        $html .= '<a href="?'. http_build_query(array(
                'tracker'  => $this->tracker_id,
                'func'     => 'admin-formElement-delete',
                'formElement' => $this->id,
            )) .'">'. $GLOBALS['HTML']->getImage('ic/cross.png', array('alt' => 'remove')) .'</a>';
        $html .= '</div>';
        $html .= $this->fetchAdminFormElement();
        $html .= '</div>';
        return $html;
    }

    /**
     * Display the html field in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html = '<hr class="tracker-admin-linebreak" id="tracker-admin-formElements_'. $this->id .'" size="1" />';
        return $html;
    }

    /**
     * getLabel - the label of this Tracker_FormElement_StaticField_LineBreak
     * The staticfield label can be internationalized.
     * To do this, fill the name field with the ad-hoc format.
     *
     * @return string label, the name if the name is not internationalized, or the localized text if so
     */
    function getLabel()
    {
        global $Language;
        $label = parent::getLabel();
        if (! $label) {
            return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'line_break_label');
        } else {
            return $label;
        }
    }

    function getDescription()
    {
        // no description for Line Break
        return '';
    }

    /**
     * @return the label of the formElement (mainly used in admin part)
     */
    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'line_break_label');
    }

    /**
     * @return the description of the formElement (mainly used in admin part)
     */
    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'line_break_description');
    }

    /**
     * @return the path to the icon to use an element
     */
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/arrow-curve-180-gray.png');
    }

    /**
     * @return the path to the icon to create an element
     */
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/arrow-curve-180-gray--plus.png');
    }
}
