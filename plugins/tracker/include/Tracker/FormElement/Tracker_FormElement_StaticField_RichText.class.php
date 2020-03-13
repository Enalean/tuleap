<?php
/**
 * Copyright (c) Enalean, 2020-present. All Rights Reserved.
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


class Tracker_FormElement_StaticField_RichText extends Tracker_FormElement_StaticField
{

    public $default_properties = array(
        'static_value' => array(
            'value' => '',
            'type'  => 'rich_text',
        ),
    );

    /**
     * The static text
     */
    protected $static_rich_text = null;

    public function getRichText()
    {
        if ($row = $this->getDao()->searchByFieldId($this->id)->getRow()) {
            $hp = Codendi_HTMLPurifier::instance();
            $value = $row['static_value'];
            return $hp->purify($value, CODENDI_PURIFIER_FULL);
        } else {
            return '';
        }
    }

    protected function getDao()
    {
        return new Tracker_FormElement_StaticField_RichTextDao();
    }


    protected function fetchReadOnly()
    {
        $html = '';
        $html .= '<div class="tracker-admin-staticrichtext" id="tracker-admin-formElements_' . $this->id . '" />';
        $html .= $this->getRichText();
        $html .= '</div>';
        return $html;
    }

    public function fetchAdmin($tracker)
    {
        $html = '';
        $html .= '<div class="tracker-admin-field" id="tracker-admin-formElements_' . $this->id . '">';
        $html .= '<div class="tracker-admin-field-controls">';
        $html .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => 'edit')) . '</a> ';
        $html .= '<a href="?' . http_build_query(array(
                'tracker'  => $this->tracker_id,
                'func'     => 'admin-formElement-remove',
                'formElement' => $this->id,
            )) . '">' . $GLOBALS['HTML']->getImage('ic/cross.png', array('alt' => 'remove')) . '</a>';
        $html .= '</div>';
        $html .= '<br />';
        $html .= $this->fetchAdminFormElement();
        $html .= '</div>';
        return $html;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html = '';
        $html .= '<div class="tracker-admin-staticrichtext" id="tracker-admin-formElements_' . $this->id . '" />';
        $html .= $this->getRichText();
        $html .= '</div>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'static_rich_text_label');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'static_rich_text_description');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/edit-drop-cap.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/edit-drop-cap--plus.png');
    }

    public function getDefaultValue()
    {
        return $this->getRichText();
    }
}
