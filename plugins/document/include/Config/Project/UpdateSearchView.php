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

namespace Tuleap\Document\Config\Project;

use Tuleap\Document\Tree\IExtractProjectFromVariables;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class UpdateSearchView implements DispatchableWithProject, DispatchableWithRequest
{
    public function __construct(
        private IExtractProjectFromVariables $project_extractor,
        private IUpdateColumns $columns_dao,
        private IUpdateCriteria $criteria_dao,
    ) {
    }

    /**
     *
     * @throws NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): \Project
    {
        return $this->project_extractor->getProject($variables);
    }

    #[\Override]
    public function process(\HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        $this->checkCsrfToken($project);

        $criteria = $request->get('criteria');
        $columns  = $request->get('columns');

        if ($this->isAnArrayOfStrings($criteria) && $this->isAnArrayOfStrings($columns)) {
            $this->criteria_dao->saveCriteria((int) $project->getID(), $criteria);
            $this->columns_dao->saveColumns((int) $project->getID(), $columns);
            $layout->addFeedback(
                \Feedback::INFO,
                dgettext('tuleap-document', 'Configuration of search view has been updated')
            );
        } else {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-document', 'Invalid request')
            );
        }

        $layout->redirect(SearchView::getUrl($project));
    }

    /**
     * @protected for testing purpose
     */
    protected function checkCsrfToken(\Project $project): void
    {
         SearchView::getCSRF($project)->check();
    }

    /**
     * @param mixed $list
     */
    private function isAnArrayOfStrings($list): bool
    {
        if (! is_array($list)) {
            return false;
        }

        foreach ($list as $element) {
            if (! is_string($element)) {
                return false;
            }
        }

        return true;
    }
}
