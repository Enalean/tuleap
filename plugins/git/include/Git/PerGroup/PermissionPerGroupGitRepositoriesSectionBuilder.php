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
use GitRepositoryFactory;
use Project;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use UGroupManager;

class PermissionPerGroupGitRepositoriesSectionBuilder
{
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    public function __construct(
        PermissionPerGroupUGroupFormatter $formatter,
        FineGrainedRetriever $fine_grained_retriever,
        GitPermissionsManager $permissions_manager,
        UGroupManager $ugroup_manager,
        GitRepositoryFactory $repository_factory
    ) {
        $this->formatter              = $formatter;
        $this->ugroup_manager         = $ugroup_manager;
        $this->repository_factory     = $repository_factory;
        $this->permissions_manager    = $permissions_manager;
        $this->fine_grained_retriever = $fine_grained_retriever;
    }

    public function buildPresenter(PermissionPerGroupPaneCollector $event)
    {
        $project               = $event->getProject();
        $repositories          = $this->repository_factory->getAllRepositoriesOfProject($project);
        $has_repositories      = count($repositories) > 0;
        $repository_presenters = [];
        $selected_ugroup_id    = $event->getSelectedUGroupId();

        foreach ($repositories as $repository) {
            if ($this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)) {
                continue;
            }

            $permissions = $this->permissions_manager->getRepositoryGlobalPermissions($repository);
            if ($selected_ugroup_id
                && ! $this->hasRepositoryAPermissionContainingUGroupId($permissions, $selected_ugroup_id)
            ) {
                continue;
            }

            $repository_presenters[] = $this->buildRepositoryPresenter(
                $repository,
                $project,
                $permissions,
                $event->getProject()
            );
        }

        $selected_ugroup = $this->ugroup_manager->getUGroup($event->getProject(), $selected_ugroup_id);

        return new RepositoriesSectionPresenter($repository_presenters, $has_repositories, $selected_ugroup);
    }

    public function hasRepositoryAPermissionContainingUGroupId(array $permissions, $selected_ugroup_id)
    {
        $is_in_array = function ($carry, $array) use ($selected_ugroup_id) {
            return $carry || in_array($selected_ugroup_id, $array);
        };

        return array_reduce($permissions, $is_in_array, false);
    }

    private function buildRepositoryPresenter(
        GitRepository $repository,
        Project $project,
        array $permissions,
        Project $project
    ) {
        $readers   = $this->formatPermissions($permissions[Git::PERM_READ], $project);
        $writers   = $this->formatPermissions($permissions[Git::PERM_WRITE], $project);
        $rewinders = $this->formatPermissions($permissions[Git::PERM_WPLUS], $project);

        $repository_name      = $repository->getFullName();
        $repository_admin_url = $this->buildAdminUrl($project, $repository);

        return new RepositoryPermissionsPresenter(
            $repository_name,
            $repository_admin_url,
            $readers,
            $writers,
            $rewinders
        );
    }

    private function formatPermissions(array $permissions, Project $project)
    {
        $formatted_permissions = [];
        foreach ($permissions as $permission) {
            $formatted_permissions[] = $this->formatter->formatGroup($project, $permission);
        }
        return $formatted_permissions;
    }

    private function buildAdminUrl(Project $project, GitRepository $repository)
    {
        $admin_url_params = http_build_query(
            [
                'action'   => 'repo_management',
                'group_id' => $project->getID(),
                'repo_id'  => $repository->getId(),
                'pane'     => 'perms'
            ]
        );
        $repository_admin_url = GIT_BASE_URL .'/?' . $admin_url_params;
        return $repository_admin_url;
    }
}
