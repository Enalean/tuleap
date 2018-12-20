<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\News\Admin\PermissionsPerGroup;

use ForgeConfig;
use Project;
use TemplateRendererFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupLoadAllButtonPresenter;
use UGroupManager;

class NewsPermissionPerGroupPaneBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        UGroupManager $ugroup_manager
    ) {
        $this->ugroup_manager = $ugroup_manager;
    }

    public function getPaneContent(Project $project, $selected_ugroup_id)
    {
        if (! $project->usesNews()) {
            return;
        }

        $tuleap_base_dir = ForgeConfig::get('tuleap_dir');
        $include_assets  = new IncludeAssets(
            $tuleap_base_dir . '/src/www/assets',
            '/assets'
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('news-permissions.js'));

        $ugroup        = $this->ugroup_manager->getUGroup($project, $selected_ugroup_id);
        $templates_dir = $tuleap_base_dir . '/src/templates/news/';
        $presenter     = new PermissionPerGroupLoadAllButtonPresenter(
            $project,
            $ugroup
        );

        return TemplateRendererFactory::build()
            ->getRenderer($templates_dir)
            ->renderToString(
                'project-admin-permission-per-group',
                $presenter
            );
    }
}
