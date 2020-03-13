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

namespace Tuleap\Project\Admin\ProjectUGroup;

use CSRFSynchronizerToken;
use ForgeConfig;
use PFUser;
use ProjectUGroup;
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
     * @var ProjectUGroupPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(
        ProjectUGroupPresenterBuilder $presenter_builder,
        IncludeAssets $include_assets,
        HeaderNavigationDisplayer $navigation_displayer
    ) {
        $this->include_assets       = $include_assets;
        $this->navigation_displayer = $navigation_displayer;
        $this->presenter_builder    = $presenter_builder;
    }

    public function display(ProjectUGroup $ugroup, CSRFSynchronizerToken $csrf, PFUser $user)
    {
        $presenter = $this->presenter_builder->build($ugroup, $csrf, $user);

        $this->displayHeader($ugroup);
        TemplateRendererFactory::build()
            ->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/project/admin/')
            ->renderToPage(
                'ugroup-settings',
                $presenter
            );
        $this->displayFooter();
    }

    private function displayHeader(ProjectUGroup $ugroup)
    {
        $title = $GLOBALS['Language']->getText('project_admin_editugroup', 'edit_ug');
        $GLOBALS['HTML']->includeFooterJavascriptFile($this->include_assets->getFileURL('project-admin.js'));
        $this->navigation_displayer->displayBurningParrotNavigation($title, $ugroup->getProject(), 'groups');
    }

    private function displayFooter()
    {
        project_admin_footer(array());
    }
}
