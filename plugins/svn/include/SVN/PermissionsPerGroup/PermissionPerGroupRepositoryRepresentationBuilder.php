<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\PermissionsPerGroup;

use Project;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryManager;

class PermissionPerGroupRepositoryRepresentationBuilder
{
    /**
     * @var RepositoryManager
     */
    private $repository_manager;

    public function __construct(RepositoryManager $repository_manager)
    {
        $this->repository_manager = $repository_manager;
    }

    public function build(Project $project)
    {
        $repositories = $this->repository_manager->getRepositoriesInProject($project);
        $permissions  = [];
        foreach ($repositories as $repository) {
            $permissions[] = new PermissionPerGroupRepositoryRepresentation(
                $repository->getName(),
                $this->getRepositoryAdminUrl($repository)
            );
        }

        return new PermissionPerGroupRepositoriesRepresentation($permissions);
    }

    private function getRepositoryAdminUrl(Repository $repository)
    {
        return SVN_BASE_URL . '/?' . http_build_query(
            [
                'group_id' => $repository->getProject()->getID(),
                'action'   => 'access-control',
                'repo_id'  => $repository->getId(),
            ]
        );
    }
}
