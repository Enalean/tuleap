<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\CanPrioritizeItems;

use ProjectUGroup;
use Tuleap\ProgramManagement\Domain\Program\Admin\CanPrioritizeItems\BuildProjectUGroupCanPrioritizeItemsPresenters;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RetrieveProjectUgroupsCanPrioritizeItems;
use Tuleap\Project\REST\UserGroupRepresentation;

final class ProjectUGroupCanPrioritizeItemsPresentersBuilder implements BuildProjectUGroupCanPrioritizeItemsPresenters
{
    private \UGroupManager $ugroup_manager;
    private \ProjectManager $project_manager;
    private RetrieveProjectUgroupsCanPrioritizeItems $can_prioritize_items_retriever;

    public function __construct(
        \UGroupManager $ugroup_manager,
        \ProjectManager $project_manager,
        RetrieveProjectUgroupsCanPrioritizeItems $can_prioritize_items_retriever
    ) {
        $this->ugroup_manager                 = $ugroup_manager;
        $this->project_manager                = $project_manager;
        $this->can_prioritize_items_retriever = $can_prioritize_items_retriever;
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    public function buildProjectUgroupCanPrioritizeItemsPresenters(int $program_id): array
    {
        $project                 = $this->project_manager->getProject($program_id);
        $ugroups                 = $this->ugroup_manager->getUGroups($project, ProjectUGroup::SYSTEM_USER_GROUPS);
        $can_prioritize_features = $this->can_prioritize_items_retriever->searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID($program_id);
        $presenters              = [];

        foreach ($ugroups as $ugroup) {
            $id = $ugroup->getId();
            if (! $ugroup->isStatic()) {
                $id = UserGroupRepresentation::getRESTIdForProject($program_id, $ugroup->getId());
            }
            $presenters[] = new ProgramSelectOptionConfigurationPresenter(
                $id,
                $ugroup->getTranslatedName(),
                in_array($ugroup->getId(), $can_prioritize_features)
            );
        }

        return $presenters;
    }
}
