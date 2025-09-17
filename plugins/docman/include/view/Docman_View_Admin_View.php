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

class Docman_View_Admin_View extends \Tuleap\Docman\View\Admin\AdminView //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string IDENTIFIER = 'admin_view';

    #[\Override]
    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
    protected function getTitle(array $params): string
    {
        return dgettext('tuleap-docman', 'Display');
    }

    public static function getTabTitle(): string
    {
        return dgettext('tuleap-docman', 'Display (legacy)');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'Define the default view for the document manager.');
    }

    #[\Override]
    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $sBo    = Docman_SettingsBo::instance($params['group_id']);
        $actual = $sBo->getView();

        $renderer->renderToPage('admin/display-preferences', [
            'is_tree'  => $actual === 'Tree' || $actual === 'Icons',
            'is_table' => $actual === 'Table',
        ]);
    }
}
