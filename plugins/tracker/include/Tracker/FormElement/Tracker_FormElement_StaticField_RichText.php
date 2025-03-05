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

use Tuleap\Tracker\Artifact\FormElement\FieldSpecificProperties\DeleteSpecificProperties;
use Tuleap\Tracker\Artifact\FormElement\FieldSpecificProperties\RichTextFieldSpecificPropertiesDAO;

class Tracker_FormElement_StaticField_RichText extends Tracker_FormElement_StaticField // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public array $default_properties = [
        'static_value' => [
            'value' => '',
            'type'  => 'rich_text',
        ],
    ];

    /**
     * The static text
     */
    protected $static_rich_text = null;

    public function getRichText()
    {
        if ($row = $this->getSearchSpecificPropertiesDao()->searchByFieldId($this->id)) {
            $hp    = Codendi_HTMLPurifier::instance();
            $value = $row['static_value'];
            return $hp->purify($value, CODENDI_PURIFIER_FULL);
        } else {
            return '';
        }
    }

    protected function getDuplicateSpecificPropertiesDao(): ?RichTextFieldSpecificPropertiesDAO
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    protected function getDeleteSpecificPropertiesDao(): DeleteSpecificProperties
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    protected function getSearchSpecificPropertiesDao(): RichTextFieldSpecificPropertiesDAO
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    protected function getSaveSpecificPropertiesDao(): RichTextFieldSpecificPropertiesDAO
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    protected function fetchReadOnly()
    {
        $html  = '';
        $html .= '<div class="tracker-admin-staticrichtext" id="tracker-admin-formElements_' . $this->id . '" />';
        $html .= $this->getRichText();
        $html .= '</div>';
        return $html;
    }

    public function fetchAdmin($tracker)
    {
        $html_purifier = Codendi_HTMLPurifier::instance();
        $html          = '<div class="tracker-admin-field" id="tracker-admin-formElements_' . $html_purifier->purify((string) $this->id) . '">';
        $html         .= '<div class="tracker-admin-field-controls">';
        $html         .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', ['alt' => 'edit']) . '</a> ';
        $csrf_token    = $this->getCSRFTokenForElementUpdate();
        $html         .= '<form method="POST" action="?">';
        $html         .= $csrf_token->fetchHTMLInput();
        $html         .= '<input type="hidden" name="func" value="' . $html_purifier->purify(\Tracker::TRACKER_ACTION_NAME_FORM_ELEMENT_REMOVE) . '" />';
        $html         .= '<input type="hidden" name="tracker" value="' . $html_purifier->purify((string) $tracker->getId()) . '" />';
        $html         .= '<input type="hidden" name="formElement" value="' . $html_purifier->purify((string) $this->id) . '" />';
        $html         .= '<button type="submit" class="btn-link">' . $GLOBALS['HTML']->getImage('ic/cross.png', ['alt' => 'remove']) . '</button>';
        $html         .= '</form>';
        $html         .= '</div>';
        $html         .= '<br />';
        $html         .= $this->fetchAdminFormElement();
        $html         .= '</div>';
        return $html;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html  = '';
        $html .= '<div class="tracker-admin-staticrichtext" id="tracker-admin-formElements_' . $this->id . '" />';
        $html .= $this->getRichText();
        $html .= '</div>';
        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Static Text');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'A static rich text element');
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
