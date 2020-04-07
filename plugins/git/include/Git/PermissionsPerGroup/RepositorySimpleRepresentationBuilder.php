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

namespace Tuleap\Git\PermissionsPerGroup;

use Git;
use GitPermissionsManager;
use GitRepository;
use Project;

class RepositorySimpleRepresentationBuilder
{
    /** @var GitPermissionsManager */
    private $permissions_manager;
    /** @var AdminUrlBuilder */
    private $url_builder;
    /**
     * @var CollectionOfUGroupRepresentationBuilder
     */
    private $collection_of_ugroups_builder;

    public function __construct(
        GitPermissionsManager $permissions_manager,
        CollectionOfUGroupRepresentationBuilder $collection_of_ugroups_builder,
        AdminUrlBuilder $url_builder
    ) {
        $this->permissions_manager = $permissions_manager;
        $this->url_builder         = $url_builder;
        $this->collection_of_ugroups_builder = $collection_of_ugroups_builder;
    }

    /**
     * @param               $selected_ugroup_id
     *
     * @return RepositoryPermissionRepresentation
     */
    public function build(
        GitRepository $repository,
        Project $project,
        $selected_ugroup_id
    ) {
        $permissions = $this->permissions_manager->getRepositoryGlobalPermissions($repository);

        if (
            $selected_ugroup_id
            && ! $this->hasRepositoryAPermissionContainingUGroupId($permissions, $selected_ugroup_id)
        ) {
            return;
        }

        if ($repository->isMigratedToGerrit()) {
            $permissions[Git::PERM_WRITE] = [];
            $permissions[Git::PERM_WPLUS] = [];
        }

        $readers   = $this->collection_of_ugroups_builder->build($project, $permissions[Git::PERM_READ]);
        $writers   = $this->collection_of_ugroups_builder->build($project, $permissions[Git::PERM_WRITE]);
        $rewinders = $this->collection_of_ugroups_builder->build($project, $permissions[Git::PERM_WPLUS]);

        $repository_name      = $repository->getFullName();
        $repository_admin_url = $this->url_builder->buildAdminUrl($repository, $project);

        return new RepositoryPermissionSimpleRepresentation(
            $repository_name,
            $repository_admin_url,
            $readers,
            $writers,
            $rewinders
        );
    }

    private function hasRepositoryAPermissionContainingUGroupId(array $permissions, $selected_ugroup_id)
    {
        $is_in_array = function ($carry, $array) use ($selected_ugroup_id) {
            return $carry || in_array($selected_ugroup_id, $array);
        };

        return array_reduce($permissions, $is_in_array, false);
    }
}
