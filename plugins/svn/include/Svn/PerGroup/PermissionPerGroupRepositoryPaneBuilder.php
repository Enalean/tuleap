<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Svn\PerGroup;

use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use Tuleap\Svn\Repository\Repository;
use Tuleap\Svn\Repository\RepositoryManager;
use UGroupManager;

class PermissionPerGroupRepositoryPaneBuilder
{
    /**
     * @var RepositoryManager
     */
    private $repository_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(RepositoryManager $repository_manager, UGroupManager $ugroup_manager)
    {
        $this->repository_manager = $repository_manager;
        $this->ugroup_manager     = $ugroup_manager;
    }

    public function buildPresenter(PermissionPerGroupPaneCollector $event)
    {
        $repositories = $this->repository_manager->getRepositoriesInProject($event->getProject());
        $ugroup       = $this->ugroup_manager->getUGroup($event->getProject(), $event->getSelectedUGroupId());

        $permissions = $this->formatRepositoriesPermissions($repositories);

        return new PermissionPerGroupPanePresenter($permissions, $ugroup);
    }

    /**
     * @param Repository[] $repositories
     *
     * @return array
     */
    private function formatRepositoriesPermissions(array $repositories)
    {
        $permission = array();
        foreach ($repositories as $repository) {
            $permission[] = array(
                'name'       => $repository->getName(),
                'id'         => $repository->getId(),
                'project_id' => $repository->getProject()->getID()
            );
        }

        return $permission;
    }
}
