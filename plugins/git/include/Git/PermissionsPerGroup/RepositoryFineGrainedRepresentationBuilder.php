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
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;

class RepositoryFineGrainedRepresentationBuilder
{
    /** @var GitPermissionsManager */
    private $permissions_manager;
    /** @var FineGrainedPermissionFactory */
    private $fine_grained_factory;
    /** @var AdminUrlBuilder */
    private $url_builder;
    /**
     * @var CollectionOfUGroupRepresentationBuilder
     */
    private $collection_of_ugroups_builder;
    /**
     * @var CollectionOfUGroupsRepresentationFormatter
     */
    private $formatter;

    public function __construct(
        GitPermissionsManager $permissions_manager,
        CollectionOfUGroupRepresentationBuilder $collection_of_ugroups_builder,
        CollectionOfUGroupsRepresentationFormatter $formatter,
        FineGrainedPermissionFactory $fine_grained_factory,
        AdminUrlBuilder $url_builder
    ) {
        $this->permissions_manager           = $permissions_manager;
        $this->fine_grained_factory          = $fine_grained_factory;
        $this->url_builder                   = $url_builder;
        $this->collection_of_ugroups_builder = $collection_of_ugroups_builder;
        $this->formatter                     = $formatter;
    }

    public function build(
        GitRepository $repository,
        Project $project,
        $selected_ugroup_id
    ) {
        $permissions = $this->permissions_manager->getRepositoryGlobalPermissions($repository);

        $readers = $this->collection_of_ugroups_builder->build(
            $project,
            $permissions[Git::PERM_READ]
        );

        $branch_permissions = $this->fine_grained_factory->getBranchesFineGrainedPermissionsForRepository(
            $repository
        );
        $tag_permissions    = $this->fine_grained_factory->getTagsFineGrainedPermissionsForRepository(
            $repository
        );

        $branch_fine_grained_permissions = $this->buildFineGrainedPermission(
            $branch_permissions,
            $project,
            false
        );
        $tag_fine_grained_permissions    = $this->buildFineGrainedPermission(
            $tag_permissions,
            $project,
            true
        );

        $repository_name      = $repository->getFullName();
        $repository_admin_url = $this->url_builder->buildAdminUrl($repository, $project);

        if (! $selected_ugroup_id) {
            return new RepositoryFineGrainedRepresentation(
                $readers,
                $repository_name,
                $repository_admin_url,
                array_merge($branch_fine_grained_permissions, $tag_fine_grained_permissions)
            );
        }

        $filtered_branch_permissions = $this->keepOnlyPermissionsContainingUgroupId(
            $branch_fine_grained_permissions,
            $selected_ugroup_id
        );
        $filtered_tag_permissions    = $this->keepOnlyPermissionsContainingUgroupId(
            $tag_fine_grained_permissions,
            $selected_ugroup_id
        );

        if (count($filtered_branch_permissions) === 0
            && count($filtered_tag_permissions) === 0
            && ! in_array($selected_ugroup_id, $permissions[Git::PERM_READ])) {
            return;
        }

        return new RepositoryFineGrainedRepresentation(
            $readers,
            $repository_name,
            $repository_admin_url,
            array_merge($filtered_branch_permissions, $filtered_tag_permissions)
        );
    }

    /**
     * @param FineGrainedPermissionRepresentation[] $fine_grained_permissions
     * @param                                       $selected_ugroup_id
     *
     * @return array
     */
    private function keepOnlyPermissionsContainingUgroupId(array $fine_grained_permissions, $selected_ugroup_id)
    {
        $filtered_permissions = [];
        foreach ($fine_grained_permissions as $permission) {
            if (in_array($selected_ugroup_id, $permission->getAllUGroupIds())) {
                $filtered_permissions[] = $permission;
            }
        }

        return $filtered_permissions;
    }

    /**
     * @param FineGrainedPermission[] $permissions
     * @param bool $is_tag
     * @return FineGrainedPermissionRepresentation[]
     */
    private function buildFineGrainedPermission(array $permissions, Project $project, $is_tag)
    {
        $fine_grained_permissions = [];
        foreach ($permissions as $fine_grained) {
            $formatted_writers   = $this->formatter->formatCollectionOfUgroups(
                $fine_grained->getWritersUgroup(),
                $project
            );
            $formatted_rewinders = $this->formatter->formatCollectionOfUgroups(
                $fine_grained->getRewindersUgroup(),
                $project
            );

            $branch = $fine_grained->getPattern();
            $tag    = "";
            if ($is_tag) {
                $branch = "";
                $tag    = $fine_grained->getPattern();
            }

            $ugroups_present_in_fine_grained = array_merge(
                array_keys($formatted_writers),
                array_keys($formatted_rewinders)
            );

            $fine_grained_permissions[] = new FineGrainedPermissionRepresentation(
                $fine_grained->getId(),
                $formatted_writers,
                $formatted_rewinders,
                $branch,
                $tag,
                $ugroups_present_in_fine_grained
            );
        }

        return $fine_grained_permissions;
    }
}
