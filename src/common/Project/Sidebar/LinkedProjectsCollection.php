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

namespace Tuleap\Project\Sidebar;

use Tuleap\Project\CheckProjectAccess;

/**
 * I contain a list of projects linked to a source project.
 * @psalm-immutable
 */
final class LinkedProjectsCollection
{
    /**
     * @var LinkedProject[]
     */
    private array $projects;

    /**
     * @param LinkedProject[] $projects
     */
    private function __construct(array $projects)
    {
        $this->projects = $projects;
    }

    public static function fromSourceProject(
        SearchLinkedProjects $searcher,
        CheckProjectAccess $access_checker,
        \Project $source_project,
        \PFUser $user,
    ): self {
        $projects        = $searcher->searchLinkedProjects($source_project);
        $linked_projects = [];
        foreach ($projects as $project) {
            $linked_project = LinkedProject::fromProject($access_checker, $project, $user);
            if (! $linked_project) {
                continue;
            }
            $linked_projects[] = $linked_project;
        }
        return new self($linked_projects);
    }

    public static function buildEmpty(): self
    {
        return new self([]);
    }

    public function merge(LinkedProjectsCollection $other_collection): self
    {
        $collection = array_merge($this->projects, $other_collection->projects);
        usort($collection, function (LinkedProject $a, LinkedProject $b) {
            return strnatcasecmp($a->public_name, $b->public_name);
        });

        return new self($collection);
    }

    /**
     * @return LinkedProject[]
     */
    public function getProjects(): array
    {
        return $this->projects;
    }

    public function isEmpty(): bool
    {
        return count($this->projects) === 0;
    }
}
