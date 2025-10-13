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

namespace Tuleap\Tracker\FormElement\View\Admin;

/**
 * Visit a target shared FormElement and provides an update view
 */
class UpdateSharedVisitorAdminView extends UpdateVisitorAdminView
{
    /**
     * Return html corresponding to FormElement update
     *
     * @return String
     */
    #[\Override]
    protected function fetchForm()
    {
        $html = '';

        $html .= $this->adminElement->fetchTypeNotModifiable();
        $html .= $this->adminElement->fetchCustomHelpForShared();
        $html .= $this->adminElement->fetchNameForShared();
        $html .= $this->adminElement->fetchLabelForShared();
        $html .= $this->adminElement->fetchDescriptionForShared();

        $html .= $this->adminElement->fetchRanking();
        $html .= $this->adminElement->fetchAdminSpecificProperties();
        $html .= $this->adminElement->fetchAfterAdminEditForm();
        $html .= $this->adminElement->fetchAdminButton(self::SUBMIT_UPDATE);
        $html .= $this->adminElement->fetchAdminFormPermissionLink();
        return $html;
    }
}
