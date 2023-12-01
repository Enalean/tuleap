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


namespace Tuleap\Test\Stubs;

use Project;
use ProjectUGroup;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;

/**
 * @see ProjectUGroupTestBuilder
 */
class UGroupRetrieverStub implements \Tuleap\Project\UGroupRetriever
{
    private function __construct(private array $ugroups_by_id, private array $ugroups_by_name)
    {
    }

    public static function buildWithUserGroups(ProjectUGroup ...$user_groups): self
    {
        $ugroups_by_id   = [];
        $ugroups_by_name = [];
        foreach ($user_groups as $user_group) {
            $ugroups_by_id[$user_group->getId()]     = $user_group;
            $ugroups_by_name[$user_group->getName()] = $user_group;
        }

        return new self($ugroups_by_id, $ugroups_by_name);
    }

    /**
     * @inheritDoc
     */
    public function getUGroup(Project $project, $ugroup_id): ?ProjectUGroup
    {
        return $this->ugroups_by_id[$ugroup_id] ?? null;
    }

    public function getUGroupByName(Project $project, string $name): ?ProjectUGroup
    {
        if (isset($this->ugroups_by_name[$name])) {
            return $this->ugroups_by_name[$name];
        }

        if (isset($this->ugroups_by_name['ugroup_' . $name . '_name_key'])) {
            return $this->ugroups_by_name['ugroup_' . $name . '_name_key'];
        }

        return null;
    }

    public function getUGroups(Project $project, array $excluded_ugroups_id = []): array
    {
        return array_values($this->ugroups_by_id);
    }
}
