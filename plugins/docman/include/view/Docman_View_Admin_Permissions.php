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

class Docman_View_Admin_Permissions extends \Tuleap\Docman\View\Admin\AdminView //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const string IDENTIFIER = 'admin_permissions';

    #[\Override]
    protected function getIdentifier(): string
    {
        return self::IDENTIFIER;
    }

    #[\Override]
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

    #[\Override]
    protected function includeStylesheets(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addCssAsset(
            new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($include_assets, 'admin-style')
        );
    }

    #[\Override]
    protected function includeJavascript(\Tuleap\Layout\IncludeAssets $include_assets): void
    {
        $GLOBALS['Response']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptAsset($include_assets, 'admin-permissions.js')
        );
    }

    public static function getCSRFToken(int $project_id): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(
            DOCMAN_BASE_URL . '/?' . http_build_query([
                'group_id' => $project_id,
                'action'   => self::IDENTIFIER,
            ])
        );
    }

    #[\Override]
    protected function displayContent(\TemplateRenderer $renderer, array $params): void
    {
        $project_id = (int) $params['group_id'];

        $dao      = new \Tuleap\Docman\Settings\SettingsDAO();
        $settings = $dao->searchByProjectId($project_id);

        $content = '<div class="tlp-framed">';

        $content .= '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . dgettext('tuleap-docman', 'Permissions') . '</h1>
                </div>
                <section class="tlp-pane-section">';

        echo $content;

        $post_url = DocmanViewURLBuilder::buildUrl(
            $params['default_url'],
            ['action' => 'admin_set_permissions'],
            false
        );

        $purifier = Codendi_HTMLPurifier::instance();
        echo '<form action="' . $purifier->purify($post_url) . '" method="post" id="docman-admin-permissions-form">
            <input type="hidden" name="func" value="update_permissions">
            <input type="hidden" name="group_id" value="' . $purifier->purify($project_id) . '">
            <input type="hidden" name="permission_type" value="PLUGIN_DOCMAN_ADMIN">
            <input type="hidden" name="object_id" value="' . $purifier->purify($project_id) . '">

            <div class="tlp-form-element">
                <label class="tlp-label" for="area">'
            . dgettext('tuleap-docman', 'User groups who can administrate the document manager, in addition of project administrators')
            . ' </label>'
            . permission_fetch_selection_field('PLUGIN_DOCMAN_ADMIN', $project_id, $project_id)
            . '
            </div>';

        $renderer->renderToPage('admin/permissions-addendum', [
            'csrf'                     => self::getCSRFToken($project_id),
            'forbid_writers_to_update' => $settings['forbid_writers_to_update'] ?? false,
            'forbid_writers_to_delete' => $settings['forbid_writers_to_delete'] ?? false,
        ]);

        echo '</form>';

        echo '</section>
            </div>
        </section>';
    }
}
