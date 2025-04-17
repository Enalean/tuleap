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

use Tuleap\Event\Dispatchable;

/**
 * I collect the list of projects linked to the source project. I can receive children projects or parent projects.
 */
final class CollectLinkedProjects implements Dispatchable
{
    public const NAME = 'collectLinkedProjects';

    private bool $can_aggregate_projects = false;

    /**
     * @psalm-readonly
     */
    private \Project $source_project;
    /**
     * @psalm-readonly
     */
    private \PFUser $current_user;
    private LinkedProjectsCollection $children_projects;
    private LinkedProjectsCollection $parent_projects;

    public function __construct(\Project $source_project, \PFUser $current_user)
    {
        $this->source_project    = $source_project;
        $this->current_user      = $current_user;
        $this->children_projects = LinkedProjectsCollection::buildEmpty();
        $this->parent_projects   = LinkedProjectsCollection::buildEmpty();
    }

    /**
     * @psalm-mutation-free
     */
    public function getChildrenProjects(): LinkedProjectsCollection
    {
        return $this->children_projects;
    }

    /**
     * @psalm-mutation-free
     */
    public function getParentProjects(): LinkedProjectsCollection
    {
        return $this->parent_projects;
    }

    /**
     * @throws CannotMixParentAndChildrenProjectsException
     */
    public function addChildrenProjects(LinkedProjectsCollection $collection): void
    {
        if (! $this->parent_projects->isEmpty()) {
            throw CannotMixParentAndChildrenProjectsException::withExistingParentProjects();
        }
        $this->children_projects = $this->children_projects->merge($collection);
    }

    /**
     * @throws CannotMixParentAndChildrenProjectsException
     */
    public function addParentProjects(LinkedProjectsCollection $collection): void
    {
        if (! $this->children_projects->isEmpty()) {
            throw CannotMixParentAndChildrenProjectsException::withExistingChildrenProjects();
        }
        $this->parent_projects = $this->parent_projects->merge($collection);
    }

    /**
     * @psalm-mutation-free
     */
    public function getSourceProject(): \Project
    {
        return $this->source_project;
    }

    /**
     * @psalm-mutation-free
     */
    public function getCurrentUser(): \PFUser
    {
        return $this->current_user;
    }

    public function projectCanAggregateProjects(): void
    {
        $this->can_aggregate_projects = true;
    }

    public function canAggregateProjects(): bool
    {
        return $this->can_aggregate_projects;
    }
}
