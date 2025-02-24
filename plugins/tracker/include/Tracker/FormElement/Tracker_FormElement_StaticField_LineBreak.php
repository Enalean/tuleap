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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_StaticField_LineBreak extends Tracker_FormElement_StaticField
{
    protected function fetchReadOnly()
    {
        $html  = '';
        $html .= '<br class="tracker-admin-linebreak" id="tracker-admin-formElements_' . $this->id . '" />';
        return $html;
    }

    public function fetchAdmin($tracker)
    {
        $html       = '';
        $hp         = Codendi_HTMLPurifier::instance();
        $html      .= '<div class="tracker-admin-field" id="tracker-admin-formElements_' . $this->id . '">';
        $html      .= '<span class="tracker-admin-form-element-help">';
        $html      .= $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
        $html      .= '</span>';
        $html      .= '<div class="tracker-admin-field-controls">';
        $html      .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', ['alt' => 'edit']) . '</a> ';
        $csrf_token = $this->getCSRFTokenForElementUpdate();
        $html      .= '<form method="POST" action="?">';
        $html      .= $csrf_token->fetchHTMLInput();
        $html      .= '<input type="hidden" name="func" value="' . $hp->purify(\Tracker::TRACKER_ACTION_NAME_FORM_ELEMENT_DELETE) . '" />';
        $html      .= '<input type="hidden" name="tracker" value="' . $hp->purify((string) $tracker->getId()) . '" />';
        $html      .= '<input type="hidden" name="formElement" value="' . $hp->purify((string) $this->id) . '" />';
        $html      .= '<button type="submit" class="btn-link">' . $GLOBALS['HTML']->getImage('ic/cross.png', ['alt' => 'remove']) . '</button>';
        $html      .= '</form>';
        $html      .= '</div>';
        $html      .= $this->fetchAdminFormElement();
        $html      .= '</div>';
        return $html;
    }

    /**
     * Display the html field in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        $html = '<hr class="tracker-admin-linebreak" id="tracker-admin-formElements_' . $this->id . '" size="1" />';
        return $html;
    }

    /**
     * getLabel - the label of this Tracker_FormElement_StaticField_LineBreak
     * The staticfield label can be internationalized.
     * To do this, fill the name field with the ad-hoc format.
     *
     * @return string label, the name if the name is not internationalized, or the localized text if so
     *
     * @psalm-mutation-free
     */
    public function getLabel()
    {
        $label = parent::getLabel();
        if (! $label) {
            return dgettext('tuleap-tracker', 'Line Break');
        } else {
            return $label;
        }
    }

    /**
     * @psalm-mutation-free
     */
    public function getDescription(): string
    {
        return '';
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Line Break');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'An invisible Line Break');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/arrow-curve-180-gray.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/arrow-curve-180-gray--plus.png');
    }
}
