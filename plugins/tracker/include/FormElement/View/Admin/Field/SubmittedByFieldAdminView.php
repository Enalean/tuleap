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

namespace Tuleap\Tracker\FormElement\View\Admin\Field;

final class SubmittedByFieldAdminView extends ListFieldAdminView
{
    #[\Override]
    protected function fetchCustomHelp()
    {
        $html  = '';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= dgettext('tuleap-tracker', 'The field is automatically set to artifact submission user');
        $html .= '</span>';
        return $html;
    }

    #[\Override]
    protected function fetchRequired()
    {
        return '';
    }

    /**
     * Fetch additionnal stuff to display below the create form
     * Result if not empty must be enclosed in a <tr>
     *
     * @return string html
     */
    #[\Override]
    public function fetchAfterAdminCreateForm()
    {
        // Don't display the values because this is a special field
        return '';
    }

    /**
     * Fetch additionnal stuff to display below the edit form
     *
     * @return string html
     */
    #[\Override]
    public function fetchAfterAdminEditForm()
    {
        // Don't display the values because this is a special field
        return '';
    }
}
