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


class Tracker_FormElement_StaticField_Separator extends Tracker_FormElement_StaticField
{

    protected function fetchReadOnly()
    {
        $html = '';
        $html .= '<hr class="tracker-field-separator" id="tracker-admin-formElements_' . $this->id . '" size="1" />';
        return $html;
    }

    public function fetchAdmin($tracker)
    {
        $html = '';
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<div class="tracker-admin-field" id="tracker-admin-formElements_' . $this->id . '">';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</span>';
        $html .= '<div class="tracker-admin-field-controls">';
        $html .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', ['alt' => 'edit']) . '</a> ';
        $html .= '<a href="?' . http_build_query([
                'tracker'  => $this->tracker_id,
                'func'     => 'admin-formElement-delete',
                'formElement' => $this->id,
            ]) . '">' . $GLOBALS['HTML']->getImage('ic/cross.png', ['alt' => 'remove']) . '</a>';
        $html .= '</div>';
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
        $html = '<hr class="tracker-admin-separator" id="tracker-admin-formElements_' . $this->id . '" size="1" />';
        return $html;
    }

    /**
     * getLabel - the label of this Tracker_FormElement_Staticfield_Separator
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
            return dgettext('tuleap-tracker', 'Separator');
        } else {
            return $label;
        }
    }

    public function getDescription()
    {
        // no description for Separator
        return '';
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Separator');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'A Line to separate elements');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-splitter-horizontal.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-splitter-horizontal--plus.png');
    }
}
