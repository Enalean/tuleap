<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Banner;

use HTTPRequest;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class BannerAdministrationController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;
    /**
     * @var HeaderNavigationDisplayer
     */
    private $navigation_displayer;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        TemplateRendererFactory $template_renderer_factory,
        HeaderNavigationDisplayer $navigation_displayer,
        ProjectManager $project_manager
    ) {
        $this->template_renderer_factory = $template_renderer_factory;
        $this->navigation_displayer      = $navigation_displayer;
        $this->project_manager           = $project_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project      = $this->getProject($variables);
        $current_user = $request->getCurrentUser();

        if (! $current_user->isAdmin($project->getID())) {
            throw new ForbiddenException();
        }

        $this->navigation_displayer->displayBurningParrotNavigation(
            _('Project banner'),
            $project,
            'banner'
        );
        $this->template_renderer_factory
            ->getRenderer(__DIR__ . '/../../../templates/project/admin/banner/')
            ->renderToPage(
                'administration',
                []
            );
        project_admin_footer([]);
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
