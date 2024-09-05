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

use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Workspace\SearchProjectsUserIsAdmin;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class SearchProjectsUserIsAdminStub implements SearchProjectsUserIsAdmin
{
    private function __construct(private array $projects)
    {
    }

    public static function buildWithoutProject(): self
    {
        return new self([]);
    }

    public static function buildWithProjects(ProjectReference ...$projects): self
    {
        return new self($projects);
    }

    public function getProjectsUserIsAdmin(UserIdentifier $user_identifier): array
    {
        return $this->projects;
    }
}
