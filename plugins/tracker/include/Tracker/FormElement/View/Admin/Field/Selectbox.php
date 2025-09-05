<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\Events\AllowedFieldTypeChangesRetriever;

class Tracker_FormElement_View_Admin_Field_Selectbox extends Tracker_FormElement_View_Admin_Field_List
{
    protected $availableTypes = ['rb', 'msb', 'cb'];

    #[\Override]
    public function fetchTypeForUpdate()
    {
        $html_purifier = Codendi_HTMLPurifier::instance();
        $html          = '<div class="tracker-admin-form-element-type-change"><label for="formElement_type">' . dgettext('tuleap-tracker', 'Type') . ': </label><br />';
        $html         .= '<img width="16" height="16" alt="" src="' . $this->formElement->getFactoryIconUseIt() . '"/> ' . $html_purifier->purify($this->formElement->getFactoryLabel());

           //do not change from SB to MSB if the field is used to define the workflow
        $wf = WorkflowFactory::instance();
        if (! $wf->isWorkflowField($this->formElement)) {
            $html .= ' (' . dgettext('tuleap-tracker', 'Switch to:') . ' ';

            $change_links = [];

            $labels     = [
                'msb' => dgettext('tuleap-tracker', 'Multi Select Box'),
                'sb' => dgettext('tuleap-tracker', 'Select Box'),
                'cb' => dgettext('tuleap-tracker', 'CheckBox'),
                'rb' => dgettext('tuleap-tracker', 'Radio button'),
            ];
            $csrf_token = $this->formElement->getCSRFTokenForElementUpdate();
            foreach ($this->getAvailableTypes() as $type) {
                $change_type_form  = '<form method="POST" action="?"';
                $change_type_form .= 'name="' . $html_purifier->purify($this->formElement->getId() . '_change_type_' . $type) . '"';
                $change_type_form .= 'onsubmit="return confirm(\'' . $html_purifier->purify(dgettext('tuleap-tracker', 'Are you sure you want to change the type of this field?'), Codendi_HTMLPurifier::CONFIG_JS_QUOTE) . '\');"';
                $change_type_form .= '>';
                $change_type_form .= $csrf_token->fetchHTMLInput();
                $change_type_form .= '<input type="hidden" name="func" value="' . $html_purifier->purify(\Tuleap\Tracker\Tracker::TRACKER_ACTION_NAME_FORM_ELEMENT_UPDATE) . '" />';
                $change_type_form .= '<input type="hidden" name="tracker" value="' . $html_purifier->purify((string) $this->formElement->getTrackerId()) . '" />';
                $change_type_form .= '<input type="hidden" name="formElement" value="' . $html_purifier->purify((string) $this->formElement->getId()) . '" />';
                $change_type_form .= '<input type="hidden" name="change-type" value="' . $html_purifier->purify($type) . '" />';
                $change_type_form .= '<button type="submit" class="btn-link">' . $html_purifier->purify($labels[$type] ?? '') . '</button>';
                $change_type_form .= '</form>';
                $change_links[]    = $change_type_form;
            }
            $html .= implode(', ', $change_links);
            $html .= ')';
        }

        $html .= '</div>';
        return $html;
    }

    public function getAvailableTypes()
    {
        $event = new AllowedFieldTypeChangesRetriever();
        $event->setField($this->formElement);

        EventManager::instance()->processEvent($event);

        if (count($event->getAllowedTypes()) > 0) {
            return $event->getAllowedTypes();
        }

        return $this->availableTypes;
    }
}
