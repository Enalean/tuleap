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

namespace Tuleap\Tracker\FormElement\StaticField\RichText;

use Codendi_HTMLPurifier;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\DeleteSpecificProperties;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\RichTextFieldSpecificPropertiesDAO;
use Tuleap\Tracker\FormElement\StaticField\TrackerStaticField;

final class RichTextStaticField extends TrackerStaticField
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

    #[\Override]
    protected function getDuplicateSpecificPropertiesDao(): ?RichTextFieldSpecificPropertiesDAO
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getDeleteSpecificPropertiesDao(): DeleteSpecificProperties
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getSearchSpecificPropertiesDao(): RichTextFieldSpecificPropertiesDAO
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function getSaveSpecificPropertiesDao(): RichTextFieldSpecificPropertiesDAO
    {
        return new RichTextFieldSpecificPropertiesDAO();
    }

    #[\Override]
    protected function fetchReadOnly()
    {
        $html  = '';
        $html .= '<div class="tracker-admin-staticrichtext" id="tracker-admin-formElements_' . $this->id . '" />';
        $html .= $this->getRichText();
        $html .= '</div>';
        return $html;
    }

    #[\Override]
    public function fetchAdmin($tracker)
    {
        $html_purifier = Codendi_HTMLPurifier::instance();
        $html          = '<div class="tracker-admin-field" id="tracker-admin-formElements_' . $html_purifier->purify((string) $this->id) . '">';
        $html         .= '<div class="tracker-admin-field-controls">';
        $html         .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', ['alt' => 'edit']) . '</a> ';
        $csrf_token    = $this->getCSRFTokenForElementUpdate();
        $html         .= '<form method="POST" action="?">';
        $html         .= $csrf_token->fetchHTMLInput();
        $html         .= '<input type="hidden" name="func" value="' . $html_purifier->purify(\Tuleap\Tracker\Tracker::TRACKER_ACTION_NAME_FORM_ELEMENT_REMOVE) . '" />';
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
    #[\Override]
    protected function fetchAdminFormElement()
    {
        $html  = '';
        $html .= '<div class="tracker-admin-staticrichtext" id="tracker-admin-formElements_' . $this->id . '" data-test="rich-text-value" />';
        $html .= $this->getRichText();
        $html .= '</div>';
        return $html;
    }

    #[\Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Static Text');
    }

    #[\Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'A static rich text element');
    }

    #[\Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/edit-drop-cap.png');
    }

    #[\Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/edit-drop-cap--plus.png');
    }

    #[\Override]
    public function getDefaultValue()
    {
        return $this->getRichText();
    }
}
