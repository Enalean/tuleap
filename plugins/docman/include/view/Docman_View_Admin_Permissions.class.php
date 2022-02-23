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

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_Admin_Permissions extends \Tuleap\Docman\View\Admin\AdminView
{
    public const IDENTIFIER = 'admin_permissions';

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
        return dgettext('tuleap-docman', 'Permissions');
    }

    public static function getTabDescription(): string
    {
        return dgettext('tuleap-docman', 'Define who can administrate the document manager.');
    }

    protected function isBurningParrotCompatiblePage(): bool
    {
        return true;
    }

    protected function includeStylesheets(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addCssAsset(
            new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($include_assets, 'admin-style')
        );
    }

    protected function includeJavascript(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptAsset($include_assets, 'admin-permissions.js')
        );
    }

    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $content = '<div class="tlp-framed">';

        $content .= '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . dgettext('tuleap-docman', 'Permissions') . '</h1>
                </div>
                <section class="tlp-pane-section">';

        $content .= '<p>';
        $content .= dgettext('tuleap-docman', 'Please select user groups that can administrate the document manager, in addition of project administrators:');
        $content .= '</p>';
        echo $content;

        $postUrl = DocmanViewURLBuilder::buildUrl($params['default_url'], [
            'action' => 'admin_set_permissions',
        ]);
        echo '<div id="docman-admin-permission-legacy-form">';
        permission_display_selection_form("PLUGIN_DOCMAN_ADMIN", $params['group_id'], $params['group_id'], $postUrl);
        echo '</div>';

        echo '</section>
            </div>
        </section>';
    }
}
