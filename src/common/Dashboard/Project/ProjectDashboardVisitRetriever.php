<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\User\History\GetVisitHistory;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryCollection;

final class ProjectDashboardVisitRetriever implements GetVisitHistory
{
    private const TYPE = 'projectdashboard';

    public function __construct(
        private readonly RecentlyVisitedProjectDashboardDao $recently_visited_project_dashboard_dao,
        private readonly ProjectDashboardRetriever $dashboard_retriever,
        private readonly ProjectByIDFactory $project_factory,
        private readonly CheckProjectAccess $check_project_access,
    ) {
    }

    public function getVisitHistory(HistoryEntryCollection $collection, int $max_length_history): void
    {
        $recently_visited_rows = $this->recently_visited_project_dashboard_dao->searchVisitByUserId(
            (int) $collection->getUser()->getId(),
            $max_length_history
        );

        foreach ($recently_visited_rows as $recently_visited_row) {
            $this->addEntry(
                $collection,
                (int) $recently_visited_row['created_on'],
                (int) $recently_visited_row['dashboard_id']
            );
        }
    }

    private function addEntry(
        HistoryEntryCollection $collection,
        int $created_on,
        int $dashboard_id,
    ): void {
        $this->dashboard_retriever
            ->getProjectDashboardById($dashboard_id)
            ->apply(
                function (ProjectDashboard $dashboard) use ($collection, $created_on): void {
                    try {
                        $project = $this->project_factory->getValidProjectById((int) $dashboard->getProjectId());
                        $this->check_project_access->checkUserCanAccessProject($collection->getUser(), $project);
                    } catch (\Project_NotFoundException | \Project_AccessException) {
                        return;
                    }

                    $url = '/projects/' . urlencode($project->getUnixName()) . '/?'
                        . http_build_query(['dashboard_id' => $dashboard->getId()]);

                    $collection->addEntry(
                        new HistoryEntry(
                            $created_on,
                            null,
                            $url,
                            sprintf(gettext('Project dashboard: %s'), $dashboard->getName()),
                            '',
                            self::TYPE,
                            (int) $dashboard->getId(),
                            null,
                            null,
                            'fa-table-cells',
                            $project,
                            [],
                            [],
                        )
                    );
                }
            );
    }
}
