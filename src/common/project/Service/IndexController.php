<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use CSRFSynchronizerToken;
use ForgeConfig;
use PFUser;
use Project;
use TemplateRendererFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;

class IndexController
{
    /**
     * @var IncludeAssets
     */
    private $include_assets;
    /**
     * @var HeaderNavigationDisplayer
     */
    private $navigation_displayer;
    /**
     * @var ServicesPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(
        ServicesPresenterBuilder $presenter_builder,
        IncludeAssets $include_assets,
        HeaderNavigationDisplayer $navigation_displayer
    ) {

        $this->include_assets       = $include_assets;
        $this->navigation_displayer = $navigation_displayer;
        $this->presenter_builder    = $presenter_builder;
    }

    public function display(Project $project, CSRFSynchronizerToken $csrf, PFUser $user)
    {
        $presenter = $this->presenter_builder->build($project, $csrf, $user);

        $this->displayHeader($project);
        TemplateRendererFactory::build()
            ->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/project/admin/')
            ->renderToPage(
                'services',
                $presenter
            );
        $this->displayFooter();
    }

    private function displayHeader(Project $project)
    {
        $title = $GLOBALS['Language']->getText('project_admin_servicebar', 'edit_s_bar');
        $GLOBALS['HTML']->includeFooterJavascriptFile($this->include_assets->getFileURL('project-admin.js'));
        $this->navigation_displayer->displayBurningParrotNavigation($title, $project, 'services');
    }

    private function displayFooter()
    {
        project_admin_footer(array());
    }
}
