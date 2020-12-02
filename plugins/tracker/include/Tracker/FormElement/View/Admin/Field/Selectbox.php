<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

    public function fetchTypeForUpdate()
    {
        $html = '';
        $html .= '<p><label for="formElement_type">' . dgettext('tuleap-tracker', 'Type') . ': </label><br />';
        $html .= '<img width="16" height="16" alt="" src="' . $this->formElement->getFactoryIconUseIt() . '" style="vertical-align:middle"/> ' . $this->formElement->getFactoryLabel();

           //do not change from SB to MSB if the field is used to define the workflow
        $wf = WorkflowFactory::instance();
        if (! $wf->isWorkflowField($this->formElement)) {
            $html .= ' (' . dgettext('tuleap-tracker', 'Switch to:') . ' ';

            $change_links = [];

            $labels = [
                'msb' => dgettext('tuleap-tracker', 'Multi Select Box'),
                'sb' => dgettext('tuleap-tracker', 'Select Box'),
                'cb' => dgettext('tuleap-tracker', 'CheckBox'),
                'rb' => dgettext('tuleap-tracker', 'Radio button'),
            ];
            foreach ($this->getAvailableTypes() as $type) {
                $change_links[] = '<a href="' . TRACKER_BASE_URL . '/?' . http_build_query([
                        'tracker'            => $this->formElement->tracker_id,
                        'func'               => 'admin-formElement-update',
                        'formElement'        => $this->formElement->id,
                        'change-type'        => $type
                    ]) . '" onclick="return confirm(\'' . dgettext('tuleap-tracker', 'Are you sure you want to change the type of this field?') . '\');">'
                       . $labels[$type] . '</a> ';
            }
            $html .= implode(', ', $change_links);
            $html .= ')';
        }

        $html .= '</p>';
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
