<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Export;

use Project;
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
use Tuleap\Request\ProjectRetriever;

final class ProjectExportController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private ProjectRetriever $project_retriever,
        private ProjectAdministratorChecker $administrator_checker,
        private ProjectAccessChecker $project_access_checker,
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

        $title = _('Project export');
        $this->displayHeader($title, $project, $layout);
        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/project');
        $renderer->renderToPage('admin/export', [
            'xml_export_href'  => '/project/' . urlencode((string) $project->getID()) . '/admin/export/xml',
        ]);
        $renderer->renderToPage('end-project-admin-content', []);
        site_project_footer([]);
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

        return $this->project_retriever->getProjectFromId($variables['project_id']);
    }

    private function displayHeader(string $title, Project $project, BaseLayout $layout): void
    {
        $header_displayer = new HeaderNavigationDisplayer();
        $header_displayer->displayBurningParrotNavigation($title, $project, NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
    }
}
