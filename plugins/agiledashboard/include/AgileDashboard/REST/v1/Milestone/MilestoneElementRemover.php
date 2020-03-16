<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Project;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;

class MilestoneElementRemover
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao
    ) {
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
    }

    /**
     * @throws RemoveNotAvailableInClassicBacklogModeException
     * @throws ProvidedRemoveIdIsNotInExplicitBacklogException
     */
    public function removeElementsFromBacklog(Project $project, array $removed): void
    {
        $project_id = (int) $project->getID();
        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id) === false) {
            throw new RemoveNotAvailableInClassicBacklogModeException();
        }

        $removed_ids = $this->getArtifactIdsFromRemoved($removed);

        $this->checkRemovedIdsBelongToTheProjectTopBacklog($removed_ids, $project_id);

        $this->artifacts_in_explicit_backlog_dao->removeItemsFromExplicitBacklogOfProject(
            $project_id,
            $removed_ids
        );
    }

    private function getArtifactIdsFromRemoved(array $removed_items): array
    {
        $ids = [];
        foreach ($removed_items as $removed_item) {
            $ids[] = (int) $removed_item->id;
        }

        return $ids;
    }

    /**
     * @throws ProvidedRemoveIdIsNotInExplicitBacklogException
     */
    private function checkRemovedIdsBelongToTheProjectTopBacklog(array $removed_ids, int $project_id): void
    {
        $ids_in_error = [];
        foreach ($removed_ids as $removed_id) {
            $in_project = $this->artifacts_in_explicit_backlog_dao->isArtifactInTopBacklogOfProject(
                $removed_id,
                $project_id
            );

            if ($in_project === false) {
                $ids_in_error[] = $removed_id;
            }
        }

        if (count($ids_in_error) > 0) {
            throw new ProvidedRemoveIdIsNotInExplicitBacklogException($ids_in_error);
        }
    }
}
