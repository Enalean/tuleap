<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\CanPrioritizeItems;

use Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramSelectOptionConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\CanPrioritizeItems\BuildUGroupRepresentation;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RetrieveProjectUgroupsCanPrioritizeItems;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUGroups;
use Tuleap\ProgramManagement\Domain\Workspace\UserGroup;

final class ProjectUGroupCanPrioritizeItemsPresentersBuilder
{
    private RetrieveProjectUgroupsCanPrioritizeItems $can_prioritize_items_retriever;
    private RetrieveUGroups $retrieve_u_groups;
    private BuildUGroupRepresentation $ugroup_representation_builder;

    public function __construct(
        RetrieveUGroups $retrieve_u_groups,
        RetrieveProjectUgroupsCanPrioritizeItems $can_prioritize_items_retriever,
        BuildUGroupRepresentation $ugroup_representation_builder,
    ) {
        $this->retrieve_u_groups              = $retrieve_u_groups;
        $this->can_prioritize_items_retriever = $can_prioritize_items_retriever;
        $this->ugroup_representation_builder  = $ugroup_representation_builder;
    }

    /**
     * @return ProgramSelectOptionConfigurationPresenter[]
     */
    public function buildProjectUgroupCanPrioritizeItemsPresenters(ProgramForAdministrationIdentifier $program): array
    {
        $ugroups                 = UserGroup::buildCollectionFromProgram($this->retrieve_u_groups, $program);
        $can_prioritize_features = $this->can_prioritize_items_retriever->searchUserGroupIDsWhoCanPrioritizeFeaturesByProjectID(
            $program->id
        );
        $presenters              = [];

        foreach ($ugroups as $ugroup) {
            $id = $ugroup->id;
            if (! $ugroup->is_created_by_user) {
                $id = $this->ugroup_representation_builder->getUGroupRepresentation($program->id, $ugroup->id);
            }
            $presenters[] = new ProgramSelectOptionConfigurationPresenter(
                $id,
                $ugroup->translated_name,
                in_array($ugroup->id, $can_prioritize_features)
            );
        }

        return $presenters;
    }
}
