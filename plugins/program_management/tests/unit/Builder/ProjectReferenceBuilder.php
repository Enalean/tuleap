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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Adapter\Workspace\ProjectReferenceProxy;
use Tuleap\ProgramManagement\Domain\ProjectReference;

final class ProjectReferenceBuilder
{
    public static function buildGeneric(): ProjectReference
    {
        return ProjectReferenceProxy::buildFromProject(
            new \Project(['group_id' => 101, 'group_name' => "My project", "unix_group_name" => "my_project"])
        );
    }

    public static function buildWithValues(int $group_id, string $group_name, string $short_name): ProjectReference
    {
        return ProjectReferenceProxy::buildFromProject(
            new \Project(['group_id' => $group_id, 'group_name' => $group_name, "unix_group_name" => $short_name])
        );
    }
}
