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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Adapter\Workspace\UserGroupProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUGroups;

final class RetrieveUGroupsStub implements RetrieveUGroups
{
    private bool $will_return_ugroups;

    private function __construct(bool $will_return_ugroups)
    {
        $this->will_return_ugroups = $will_return_ugroups;
    }

    public static function buildWithUGroups(): self
    {
        return new self(true);
    }

    public static function buildWithNoUGroups(): self
    {
        return new self(false);
    }

    #[\Override]
    public function getUgroupsFromProgram(ProgramForAdministrationIdentifier $program): array
    {
        return [
            UserGroupProxy::fromProjectUGroup(new \ProjectUGroup(['ugroup_id' => 3])),
            UserGroupProxy::fromProjectUGroup(new \ProjectUGroup(['ugroup_id' => 105])),
        ];
    }

    #[\Override]
    public function getUGroupByNameInProgram(ProgramForAdministrationIdentifier $program, string $ugroup_name): ?UserGroupProxy
    {
        if ($this->will_return_ugroups === false) {
            return null;
        }

        return UserGroupProxy::fromProjectUGroup(new \ProjectUGroup(['ugroup_id' => 3]));
    }
}
