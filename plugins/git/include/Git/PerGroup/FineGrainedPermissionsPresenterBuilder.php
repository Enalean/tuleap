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
use ProjectUGroup;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;

class FineGrainedPermissionsPresenterBuilder
{
    /** @var GitPermissionsManager */
    private $permissions_manager;
    /** @var CollectionOfUgroupsFormatter */
    private $formatter;
    /** @var FineGrainedPermissionFactory */
    private $fine_grained_factory;
    /** @var AdminUrlBuilder */
    private $url_builder;

    public function __construct(
        GitPermissionsManager $permissions_manager,
        CollectionOfUgroupsFormatter $formatter,
        FineGrainedPermissionFactory $fine_grained_factory,
        AdminUrlBuilder $url_builder
    ) {
        $this->permissions_manager  = $permissions_manager;
        $this->formatter            = $formatter;
        $this->fine_grained_factory = $fine_grained_factory;
        $this->url_builder          = $url_builder;
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
        $permissions        = $this->permissions_manager->getRepositoryGlobalPermissions($repository);
        $readers            = $permissions[Git::PERM_READ];
        $branch_permissions = $this->fine_grained_factory->getBranchesFineGrainedPermissionsForRepository(
            $repository
        );
        $tag_permissions    = $this->fine_grained_factory->getTagsFineGrainedPermissionsForRepository(
            $repository
        );

        if (! $selected_ugroup_id) {
            $collection->addPresenter($this->buildPresenter(
                $repository,
                $project,
                $readers,
                $branch_permissions,
                $tag_permissions
            ));
            return;
        }

        $filtered_branch_permissions = $this->keepOnlyPermissionsContainingUgroupId($branch_permissions, $selected_ugroup_id);
        $filtered_tag_permissions    = $this->keepOnlyPermissionsContainingUgroupId($tag_permissions, $selected_ugroup_id);

        if ($this->doesRepositoryHaveNoPermissionMatchingUgroupId(
            $filtered_branch_permissions,
            $filtered_tag_permissions,
            $readers,
            $selected_ugroup_id
        )) {
            return;
        }

        $collection->addPresenter($this->buildPresenter(
            $repository,
            $project,
            $readers,
            $filtered_branch_permissions,
            $filtered_tag_permissions
        ));
    }

    private function doesRepositoryHaveNoPermissionMatchingUgroupId(
        array $branch_permissions,
        array $tag_permissions,
        array $readers,
        $selected_ugroup_id
    ) {
        return (count($branch_permissions) === 0
            && count($tag_permissions) === 0
            && ! in_array($selected_ugroup_id, $readers)
        );
    }

    /**
     * @param FineGrainedPermission[] $permissions
     * @param int $selected_ugroup_id
     * @return array
     */
    private function keepOnlyPermissionsContainingUgroupId(array $permissions, $selected_ugroup_id)
    {
        $is_in_perms = function ($permission) use ($selected_ugroup_id) {
            return ($this->isInArrayOfUgroups($selected_ugroup_id, $permission->getWritersUgroup())
                || $this->isInArrayOfUgroups($selected_ugroup_id, $permission->getRewindersUgroup()));
        };

        return array_filter($permissions, $is_in_perms);
    }

    /**
     * @param $id
     * @param ProjectUGroup[] $array_of_ugroups
     * @return bool
     */
    private function isInArrayOfUgroups($id, array $array_of_ugroups)
    {
        foreach ($array_of_ugroups as $ugroup) {
            if ($ugroup->getId() === $id) {
                return true;
            }
        }

        return false;
    }

    private function buildPresenter(
        GitRepository $repository,
        Project $project,
        array $read_ugroup_ids,
        array $branch_permissions,
        array $tag_permissions
    ) {
        $readers           = $this->formatter->formatCollectionOfUgroupIds(
            $read_ugroup_ids,
            $project
        );
        $branch_presenters = $this->buildFineGrainedPermissionPresenters(
            $branch_permissions,
            $project,
            false
        );
        $tag_presenters    = $this->buildFineGrainedPermissionPresenters(
            $tag_permissions,
            $project,
            true
        );

        $fine_grained_permissions = array_merge($branch_presenters, $tag_presenters);

        $repository_name      = $repository->getFullName();
        $repository_admin_url = $this->url_builder->buildAdminUrl($repository, $project);
        return new FineGrainedPermissionsPresenter(
            $repository_name,
            $repository_admin_url,
            $readers,
            $fine_grained_permissions
        );
    }

    private function buildFineGrainedPermissionPresenters(array $permissions, Project $project, $is_tag)
    {
        $formatted = [];
        foreach ($permissions as $fine_grained) {
            $formatted_writers   = $this->formatter->formatCollectionOfUgroups(
                $fine_grained->getWritersUgroup(),
                $project
            );
            $formatted_rewinders = $this->formatter->formatCollectionOfUgroups(
                $fine_grained->getRewindersUgroup(),
                $project
            );
            $formatted[]         = new FineGrainedRowPresenter(
                $fine_grained->getPattern(),
                $is_tag,
                $formatted_writers,
                $formatted_rewinders
            );
        }
        return $formatted;
    }
}
