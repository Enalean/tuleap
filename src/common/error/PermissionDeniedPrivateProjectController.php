<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

namespace Tuleap\Error;

use ForgeConfig;
use PFUser;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use ThemeManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;

class PermissionDeniedPrivateProjectController
{
    /**
     * @var ThemeManager
     */
    private $theme_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var PlaceHolderBuilder
     */
    private $place_holder_builder;

    public function __construct(
        ThemeManager $theme_manager,
        ProjectManager $project_manager,
        PlaceHolderBuilder $place_holder_builder
    ) {
        $this->theme_manager        = $theme_manager;
        $this->project_manager      = $project_manager;
        $this->place_holder_builder = $place_holder_builder;
    }

    public function displayError(PFUser $user, Project $project = null)
    {
        $layout = $this->theme_manager->getBurningParrot($user);

        $layout->header(["title" => _("Project access error")]);

        $renderer = TemplateRendererFactory::build()->getRenderer(
            ForgeConfig::get('codendi_dir') . '/src/templates/admin/projects/'
        );

        $this->includeJavascriptDependencies($layout);

        $placeholder = $this->place_holder_builder->buildPlaceHolder($project);

        $renderer->renderToPage(
            'permission-denied-private-project',
            new PermissionDeniedPrivateProjectPresenter($project, $this->getToken(), $placeholder)
        );


        $layout->footer([]);
    }

    private function includeJavascriptDependencies(BaseLayout $layout)
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets',
            '/assets'
        );

        $layout->includeFooterJavascriptFile($include_assets->getFileURL('access-denied-error.js'));
    }

    /**
     * @return \CSRFSynchronizerToken
     */
    private function getToken()
    {
        return new \CSRFSynchronizerToken("/join-private-project-mail/");
    }
}
