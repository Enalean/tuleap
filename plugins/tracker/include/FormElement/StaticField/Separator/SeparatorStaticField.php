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

namespace Tuleap\Tracker\FormElement\StaticField\Separator;

use Codendi_HTMLPurifier;
use Tuleap\Tracker\FormElement\StaticField\TrackerStaticField;

final class SeparatorStaticField extends TrackerStaticField
{
    #[\Override]
    protected function fetchReadOnly()
    {
        $html  = '';
        $html .= '<hr class="tracker-field-separator" id="tracker-admin-formElements_' . $this->id . '" size="1" />';
        return $html;
    }

    #[\Override]
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
        $html      .= '<input type="hidden" name="func" value="' . $hp->purify(\Tuleap\Tracker\Tracker::TRACKER_ACTION_NAME_FORM_ELEMENT_DELETE) . '" />';
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
     * @return string html
     */
    #[\Override]
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
    #[\Override]
    public function getLabel()
    {
        $label = parent::getLabel();
        if (! $label) {
            return dgettext('tuleap-tracker', 'Separator');
        } else {
            return $label;
        }
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Separator');
    }

    #[\Override]
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'A Line to separate elements');
    }

    #[\Override]
    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-splitter-horizontal.png');
    }

    #[\Override]
    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/ui-splitter-horizontal--plus.png');
    }
}
