<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\FRS\PermissionsPerGroup;

use Project;
use TemplateRendererFactory;
use Tuleap\Layout\JavascriptViteAsset;

class PaneCollector
{
    /**
     * @var PermissionPerGroupFRSServicePresenterBuilder
     */
    private $service_presenter_builder;
    /**
     * @var PermissionPerGroupFRSPackagesPresenterBuilder
     */
    private $packages_pane_builder;

    public function __construct(
        PermissionPerGroupFRSServicePresenterBuilder $service_presenter_builder,
        PermissionPerGroupFRSPackagesPresenterBuilder $packages_pane_builder,
    ) {
        $this->service_presenter_builder = $service_presenter_builder;
        $this->packages_pane_builder     = $packages_pane_builder;
    }

    public function collectPane(Project $project, $selected_ugroup = null): string
    {
        if (! $project->usesFile()) {
            return '';
        }

        $service_presenter = $this->service_presenter_builder->getPanePresenter($project, $selected_ugroup);
        $package_presenter = $this->packages_pane_builder->getPanePresenter($project, $selected_ugroup);

        $include_assets = new \Tuleap\Layout\IncludeViteAssets(
            __DIR__ . '/../../../scripts/frs-permissions-per-group/frontend-assets',
            '/assets/core/frs-permissions-per-group'
        );

        $GLOBALS['HTML']->addJavascriptAsset(new JavascriptViteAsset($include_assets, 'src/index.ts'));

        $global_presenter = new GlobalPresenter($service_presenter, $package_presenter);

        $templates_dir = __DIR__ . '/../../../templates/frs';
        $content       = TemplateRendererFactory::build()
            ->getRenderer($templates_dir)
            ->renderToString('project-admin-permission-per-group', $global_presenter);

        return $content;
    }
}
