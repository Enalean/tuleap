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

namespace Tuleap\Git\PerGroup;

use Git;
use GitPermissionsManager;
use GitRepository;
use Project;

class SimplePermissionsPresenterBuilder
{
    /** @var GitPermissionsManager */
    private $permissions_manager;
    /** @var CollectionOfUgroupsFormatter */
    private $formatter;
    /** @var AdminUrlBuilder */
    private $url_builder;

    public function __construct(
        GitPermissionsManager $permissions_manager,
        CollectionOfUgroupsFormatter $formatter,
        AdminUrlBuilder $url_builder
    ) {
        $this->permissions_manager = $permissions_manager;
        $this->formatter           = $formatter;
        $this->url_builder         = $url_builder;
    }

    /**
     * @param RepositoryPermissionsPresenterCollection $collection
     * @param GitRepository $repository
     * @param Project $project
     * @param int $selected_ugroup_id
     */
    public function addPresenterToCollection(
        RepositoryPermissionsPresenterCollection $collection,
        GitRepository $repository,
        Project $project,
        $selected_ugroup_id
    ) {
        $permissions = $this->permissions_manager->getRepositoryGlobalPermissions($repository);

        if ($selected_ugroup_id
            && ! $this->hasRepositoryAPermissionContainingUGroupId($permissions, $selected_ugroup_id)
        ) {
            return;
        }

        $readers   = $this->formatter->formatCollectionOfUgroupIds($permissions[Git::PERM_READ], $project);
        $writers   = $this->formatter->formatCollectionOfUgroupIds($permissions[Git::PERM_WRITE], $project);
        $rewinders = $this->formatter->formatCollectionOfUgroupIds($permissions[Git::PERM_WPLUS], $project);

        $repository_name      = $repository->getFullName();
        $repository_admin_url = $this->url_builder->buildAdminUrl($repository, $project);

        $collection->addPresenter(
            new SimplePermissionsPresenter(
                $repository_name,
                $repository_admin_url,
                $readers,
                $writers,
                $rewinders
            )
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
