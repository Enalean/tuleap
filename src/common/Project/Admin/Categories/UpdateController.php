<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\Categories;

use Feedback;
use HTTPRequest;
use Project;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class UpdateController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var ProjectCategoriesUpdater
     */
    private $updater;

    public function __construct(ProjectManager $project_manager, ProjectCategoriesUpdater $updater)
    {
        $this->project_manager = $project_manager;
        $this->updater = $updater;
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProject($variables['id']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(gettext("Project does not exist"));
        }

        return $project;
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        if (! $request->getCurrentUser()->isAdmin($project->getId())) {
            throw new ForbiddenException(gettext("You don't have permission to access administration of this project."));
        }

        $redirect_url  = '/project/' . (int) $project->getID() . '/admin/categories';

        $categories = $request->get('categories');
        if (! is_array($categories)) {
            $layout->addFeedback(Feedback::ERROR, gettext("Your request is invalid"));
            $layout->redirect($redirect_url);
            return;
        }

        $csrf = new \CSRFSynchronizerToken($redirect_url);
        $csrf->check();

        try {
            $this->updater->update($project, CategoryCollection::buildFromWebPayload($categories));
            $layout->addFeedback(Feedback::INFO, gettext("Categories successfully updated."));
        } catch (MissingMandatoryCategoriesException $exception) {
            $layout->addFeedback(Feedback::ERROR, _('Some mandatory categories are missing'));
        } catch (ProjectCategoriesException $exception) {
            $layout->addFeedback(Feedback::ERROR, _('Invalid selection of categories'));
        }

        $layout->redirect($redirect_url);
    }
}
