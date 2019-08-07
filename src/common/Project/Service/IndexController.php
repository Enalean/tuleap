<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use HTTPRequest;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class IndexController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    private const CSRF_TOKEN = 'project_admin_services';

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
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        ServicesPresenterBuilder $presenter_builder,
        IncludeAssets $include_assets,
        HeaderNavigationDisplayer $navigation_displayer,
        ProjectManager $project_manager
    ) {

        $this->include_assets       = $include_assets;
        $this->navigation_displayer = $navigation_displayer;
        $this->presenter_builder    = $presenter_builder;
        $this->project_manager = $project_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        $current_user = $request->getCurrentUser();

        if (! $current_user->isAdmin($project->getID())) {
            throw new ForbiddenException();
        }

        $presenter = $this->presenter_builder->build($project, self::getCSRFTokenSynchronizer(), $request->getCurrentUser());

        $this->displayHeader($project, $layout, $current_user);
        TemplateRendererFactory::build()
            ->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/project/admin/services/')
            ->renderToPage(
                'services',
                $presenter
            );
        $this->displayFooter();
    }

    private function displayHeader(Project $project, BaseLayout $layout, \PFUser $current_user): void
    {
        $title = $GLOBALS['Language']->getText('project_admin_servicebar', 'edit_s_bar');
        $javascript_file_name = 'project-admin-services.js';
        if ($current_user->isSuperUser()) {
            $javascript_file_name = 'site-admin-services.js';
        }
        $layout->includeFooterJavascriptFile($this->include_assets->getFileURL($javascript_file_name));
        $this->navigation_displayer->displayBurningParrotNavigation($title, $project, 'services');
    }

    private function displayFooter()
    {
        project_admin_footer(array());
    }

    public static function getCSRFTokenSynchronizer(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(self::CSRF_TOKEN);
    }

    public static function getUrl(Project $project) : string
    {
        return sprintf('/project/%s/admin/services', urlencode((string) $project->getID()));
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProject($variables['id']);
        if (! $project || $project->isError()) {
            throw new NotFoundException();
        }
        return $project;
    }
}
