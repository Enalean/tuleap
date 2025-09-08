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
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

readonly class ReferenceAdministrationBrowseController implements DispatchableWithBurningParrot, DispatchableWithRequest, DispatchableWithProject
{
    public function __construct(
        private ProjectManager $project_manager,
        private ReferenceAdministrationBrowsingRenderer $renderer,
        private HeaderNavigationDisplayer $header_displayer,
        private ProjectAccessChecker $project_access_checker,
        private ProjectAdministratorChecker $administrator_checker,
    ) {
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    #[\Override]
    public function process(\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        $user    = $request->getCurrentUser();

        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (\Exception $e) {
            throw new ForbiddenException();
        }

        $this->administrator_checker->checkUserIsProjectAdministrator($user, $project);

        $this->header_displayer->displayBurningParrotNavigation(_('Editing reference patterns'), $project, NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME);

        $this->renderer->render($project);
        $layout->footer([]);
    }

    /**
     * @throws NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): \Project
    {
        if (! isset($variables['project_id'])) {
            throw new NotFoundException();
        }
        $project = $this->project_manager->getProject($variables['project_id']);
        if ($project->isError()) {
            throw new NotFoundException();
        }
        return $project;
    }
}
