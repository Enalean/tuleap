<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Docman_View_Admin_View extends \Tuleap\Docman\View\Admin\AdminView
{
    public const IDENTIFIER = 'admin_view';

    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    protected function getTitle(array $params): string
    {
        return self::getTabTitle();
    }

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-docman', 'Manage Display Preferences');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'Define the default view for the document manager.');
    }

    protected function displayContent(array $params): void
    {
        $html  = '';
        $html .= '<p>' . dgettext('tuleap-docman', 'Please select the default view for browsing documents. Please note that this setting can be overridden by user preferences.') . '</p>';
        $html .= '<form action="' . $params['default_url'] . '" method="POST">';
        $html .= '<select name="selected_view" onchange="this.form.submit()">';

        $sBo    = Docman_SettingsBo::instance($params['group_id']);
        $actual = $sBo->getView();

        $html .= '<option value="Tree" ' . ($actual === 'Tree' ? 'selected="selected"' : '') . '>';
        $html .= dgettext('tuleap-docman', 'Tree');
        $html .= '</option>';
        $html .= '<option value="Icons" ' . ($actual === 'Icons' ? 'selected="selected"' : '') . '>';
        $html .= dgettext('tuleap-docman', 'Icons');
        $html .= '</option>';
        $html .= '<option value="Table" ' . ($actual === 'Table' ? 'selected="selected"' : '') . '>';
        $html .= dgettext('tuleap-docman', 'Table');
        $html .= '</option>';


        $html .= '</select>';
        $html .= '<input type="hidden" name="action" value="admin_change_view" />';
        $html .= '<noscript><input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" /></noscript>';
        echo $html;
    }
}
