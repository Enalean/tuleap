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

/**
 * @psalm-immutable
 */
final class LinkedProjectsCollectionPresenter
{
    public int $nb_of_linked_projects;
    public bool $is_in_children_projects_context;
    /**
     * @var LinkedProjectPresenter[]
     */
    public array $projects;

    /**
     * @param LinkedProjectPresenter[] $projects
     */
    private function __construct(bool $is_in_children_projects_context, array $projects)
    {
        $this->is_in_children_projects_context = $is_in_children_projects_context;
        $this->projects                        = $projects;
        $this->nb_of_linked_projects           = count($projects);
    }

    public static function fromEvent(CollectLinkedProjects $event): ?self
    {
        $children_projects = $event->getChildrenProjects();
        if (! $children_projects->isEmpty()) {
            $presenters = [];
            foreach ($children_projects->getProjects() as $child_project) {
                $presenters[] = LinkedProjectPresenter::fromLinkedProject($child_project);
            }
            return new self(true, $presenters);
        }
        $parent_projects = $event->getParentProjects();
        if (! $parent_projects->isEmpty()) {
            $presenters = [];
            foreach ($parent_projects->getProjects() as $parent_project) {
                $presenters[] = LinkedProjectPresenter::fromLinkedProject($parent_project);
            }
            return new self(false, $presenters);
        }
        return null;
    }
}
