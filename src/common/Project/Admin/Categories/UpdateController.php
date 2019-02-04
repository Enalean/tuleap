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

use HTTPRequest;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use TroveCatDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class UpdateController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var TroveCatDao
     */
    private $dao;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;

    public function __construct(TroveCatDao $dao, ProjectHistoryDao $history_dao)
    {
        $this->dao         = $dao;
        $this->history_dao = $history_dao;
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(\HTTPRequest $request, array $variables): Project
    {
        $project = ProjectManager::instance()->getProject($variables['id']);
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
        $project = $this->getProject($request, $variables);
        if (! $request->getCurrentUser()->isAdmin($project->getId())) {
            throw new ForbiddenException(gettext("You don't have permission to access administration of this project."));
        }

        $url  = '/project/' . (int) $project->getID() . '/admin/categories';
        $csrf = new \CSRFSynchronizerToken($url);
        $csrf->check();

        $categories = $request->get('categories');
        if (! is_array($categories)) {
            $layout->addFeedback(\Feedback::ERROR, gettext("Your request is invalid"));
            $layout->redirect($url);
        }

        $top_categories_ids = [];
        foreach ($this->dao->getTopCategories() as $row) {
            $top_categories_ids[$row['trove_cat_id']] = true;
        }

        $this->history_dao->groupAddHistory('changed_trove', "", $project->getID());
        foreach ($categories as $root_id => $trove_cat_ids) {
            if (! isset($top_categories_ids[$root_id])) {
                continue;
            }

            if (! is_array($trove_cat_ids)) {
                continue;
            }

            $this->dao->removeProjectTopCategoryValue($project->getID(), $root_id);

            $first_trove_cat_ids = \array_slice($trove_cat_ids, 0, IndexController::TROVE_MAXPERROOT);
            foreach ($first_trove_cat_ids as $submitted_category_id) {
                \trove_setnode($project->getID(), (int) $submitted_category_id, $root_id);
            }
        }

        $layout->addFeedback(\Feedback::INFO, gettext("Categories successfully updated."));
        $layout->redirect($url);
    }
}
