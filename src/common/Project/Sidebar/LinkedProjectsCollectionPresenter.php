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
    public const NB_MAX_PROJECTS_BEFORE_POPOVER = 5;

    /**
     * @param LinkedProjectPresenter[] $projects
     */
    private function __construct(
        public string $label,
        public bool $is_in_children_projects_context,
        public int $nb_max_projects_before_popover,
        public array $projects,
    ) {
    }

    public static function fromEvent(CollectLinkedProjects $event): ?self
    {
        $children_projects = $event->getChildrenProjects();
        if (! $children_projects->isEmpty()) {
            $presenters = [];
            foreach ($children_projects->getProjects() as $child_project) {
                $presenters[] = LinkedProjectPresenter::fromLinkedProject($child_project);
            }
            $nb_linked_projects = count($presenters);
            return new self(
                sprintf(
                    ngettext(
                        '%d Aggregated project',
                        '%d Aggregated projects',
                        $nb_linked_projects
                    ),
                    $nb_linked_projects
                ),
                true,
                self::NB_MAX_PROJECTS_BEFORE_POPOVER,
                $presenters
            );
        }
        $parent_projects = $event->getParentProjects();
        if (! $parent_projects->isEmpty()) {
            $presenters = [];
            foreach ($parent_projects->getProjects() as $parent_project) {
                $presenters[] = LinkedProjectPresenter::fromLinkedProject($parent_project);
            }
            $nb_linked_projects = count($presenters);
            return new self(
                sprintf(
                    ngettext(
                        '%d Parent project',
                        '%d Parent projects',
                        $nb_linked_projects
                    ),
                    $nb_linked_projects
                ),
                false,
                self::NB_MAX_PROJECTS_BEFORE_POPOVER,
                $presenters
            );
        }
        return null;
    }
}
