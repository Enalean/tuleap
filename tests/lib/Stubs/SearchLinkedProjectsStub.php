<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\Project\Sidebar\SearchLinkedProjects;

final class SearchLinkedProjectsStub implements SearchLinkedProjects
{
    /**
     * @var \Project[]
     */
    private array $projects;

    /**
     * @param \Project[] $projects
     */
    private function __construct(array $projects)
    {
        $this->projects = $projects;
    }

    public static function withValidProjects(\Project ...$projects): self
    {
        return new self($projects);
    }

    #[\Override]
    public function searchLinkedProjects(\Project $source_project): array
    {
        return $this->projects;
    }
}
