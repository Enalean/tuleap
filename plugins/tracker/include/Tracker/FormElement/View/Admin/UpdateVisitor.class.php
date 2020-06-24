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


/**
 * Visit a FormElement and provides an update view
 */
class Tracker_FormElement_View_Admin_UpdateVisitor extends Tracker_FormElement_View_Admin_Visitor
{

    protected function fetchForm()
    {
        $html = '';

        $html .= $this->adminElement->fetchTypeForUpdate();
        $html .= $this->adminElement->fetchNameForUpdate();
        $html .= $this->adminElement->fetchLabelForUpdate();
        $html .= $this->adminElement->fetchDescriptionForUpdate();

        $html .= $this->adminElement->fetchRanking();
        $html .= $this->adminElement->fetchAdminSpecificProperties();
        $html .= $this->adminElement->fetchAfterAdminEditForm();
        $html .= $this->adminElement->fetchAdminButton(self::SUBMIT_UPDATE);
        $html .= $this->adminElement->fetchAdminFormPermissionLink();
        $html .= $this->adminElement->fetchSharedUsage();
        return $html;
    }

    /**
     * Display the form to administrate the element
     *
     * @param TrackerManager  $tracker_manager The tracker manager
     * @param HTTPRequest     $request         The data coming from the user
     *
     * @return void
     */
    public function display(TrackerManager $tracker_manager, HTTPRequest $request)
    {
        $label            = $this->element->getLabel();
        $title            = sprintf(dgettext('tuleap-tracker', 'Update Field \'%1$s\''), $label);
        $url              = $this->element->getAdminEditUrl();

        echo $this->displayForm($tracker_manager, $request, $url, $title, $this->fetchForm());
    }
}
