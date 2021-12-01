<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\Reference\Browse;

use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class ReferenceAdministrationBrowseController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var LegacyReferenceAdministrationBrowsingRenderer
     */
    private $legacy_renderer;
    /**
     * @var HeaderNavigationDisplayer
     */
    private $header_navigation_displayer;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;

    public function __construct(
        ProjectManager $project_manager,
        LegacyReferenceAdministrationBrowsingRenderer $legacy_renderer,
        HeaderNavigationDisplayer $header_navigation_displayer,
        ProjectAccessChecker $project_access_checker,
    ) {
        $this->project_manager             = $project_manager;
        $this->legacy_renderer             = $legacy_renderer;
        $this->header_navigation_displayer = $header_navigation_displayer;
        $this->project_access_checker      = $project_access_checker;
    }
    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);

        try {
            $this->project_access_checker->checkUserCanAccessProject($request->getCurrentUser(), $project);
        } catch (\Exception $e) {
            throw new ForbiddenException();
        }

        $this->header_navigation_displayer->displayFlamingParrotNavigation(
            _('Editing reference patterns'),
            $project,
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME
        );

        $this->legacy_renderer->render($project);
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(array $variables): \Project
    {
        if (! isset($variables['id'])) {
            throw new NotFoundException();
        }
        $project = $this->project_manager->getProject($variables['id']);
        if ($project->isError()) {
            throw new NotFoundException();
        }
        return $project;
    }
}
