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

namespace Tuleap\Project\Admin\Categories;

use Feedback;
use HTTPRequest;
use ProjectHistoryDao;
use TroveCatDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

class UpdateController implements DispatchableWithRequest
{
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var ProjectAdministratorChecker
     */
    private $administrator_checker;
    /**
     * @var ProjectCategoriesUpdater
     */
    private $updater;

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        ProjectCategoriesUpdater $updater
    ) {
        $this->project_retriever     = $project_retriever;
        $this->administrator_checker = $administrator_checker;
        $this->updater               = $updater;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            new ProjectCategoriesUpdater(
                new \TroveCatFactory(new TroveCatDao()),
                new ProjectHistoryDao(),
                new TroveSetNodeFacade()
            )
        );
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->project_retriever->getProjectFromId($variables['id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);
        $redirect_url = '/project/' . (int) $project->getID() . '/admin/categories';

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
