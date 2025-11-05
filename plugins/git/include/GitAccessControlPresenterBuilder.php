<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git;

use CSRFSynchronizerToken;
use Git;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use GitPermissionsManager;
use GitPresenters_AccessControlPresenter;
use GitRepository;
use PFUser;
use Project;
use Tuleap\Git\Permissions\DefaultFineGrainedPermission;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;

readonly class GitAccessControlPresenterBuilder
{
    public function __construct(
        private AccessRightsPresenterOptionsBuilder $access_rights_presenter_options_builder,
        private DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        private FineGrainedRetriever $fine_grained_retriever,
        private FineGrainedRepresentationBuilder $fine_grained_representation_builder,
        private FineGrainedPermissionFactory $fine_grained_permission_factory,
        private Git_Driver_Gerrit_ProjectCreatorStatus $gerrit_project_creator_status,
        private GitPermissionsManager $git_permissions_manager,
        private RegexpFineGrainedRetriever $regexp_fine_grained_retriever,
    ) {
    }

    public function buildForSingleRepositoryFork(GitRepository $repository, Project $project, PFUser $user): GitPresenters_AccessControlPresenter
    {
        return $this->buildForSingleRepository($repository, $project, $user, true);
    }

    public function buildForPermissionsManagement(GitRepository $repository, Project $project, PFUser $user): GitPresenters_AccessControlPresenter
    {
        return $this->buildForSingleRepository($repository, $project, $user, false);
    }

    private function buildForSingleRepository(GitRepository $repository, Project $project, PFUser $user, bool $is_forking_repository): GitPresenters_AccessControlPresenter
    {
        return new GitPresenters_AccessControlPresenter(
            ! $this->gerrit_project_creator_status->canModifyPermissionsTuleapSide($repository),
            'repo_access[' . Git::PERM_READ . ']',
            'repo_access[' . Git::PERM_WRITE . ']',
            'repo_access[' . Git::PERM_WPLUS . ']',
            $this->access_rights_presenter_options_builder->getOptions($project, $repository, Git::PERM_READ),
            $this->access_rights_presenter_options_builder->getOptions($project, $repository, Git::PERM_WRITE),
            $this->access_rights_presenter_options_builder->getOptions($project, $repository, Git::PERM_WPLUS),
            $this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository),
            $this->canUserSetFineGrainedPermissions($repository, $project, $user, $is_forking_repository),
            $this->getFineGrainedPermissionsRepresentations(
                $project,
                $this->fine_grained_permission_factory->getBranchesFineGrainedPermissionsForRepository($repository),
            ),
            $this->getFineGrainedPermissionsRepresentations(
                $project,
                $this->fine_grained_permission_factory->getTagsFineGrainedPermissionsForRepository($repository),
            ),
            $this->access_rights_presenter_options_builder->getAllOptions($project),
            $this->getDeleteUrl($repository, $project),
            $this->getCSRFToken($project),
            $is_forking_repository,
            $this->regexp_fine_grained_retriever->areRegexpActivatedAtSiteLevel(),
            $this->regexp_fine_grained_retriever->areRegexpActivatedForRepository($repository),
            $this->getWarningContentForRegexpDisableModal($repository)
        );
    }

    public function buildWithDefaults(GitRepository $repository, Project $project): GitPresenters_AccessControlPresenter
    {
        return new GitPresenters_AccessControlPresenter(
            ! $this->gerrit_project_creator_status->canModifyPermissionsTuleapSide($repository),
            'repo_access[' . Git::PERM_READ . ']',
            'repo_access[' . Git::PERM_WRITE . ']',
            'repo_access[' . Git::PERM_WPLUS . ']',
            $this->access_rights_presenter_options_builder->getDefaultOptions($project, Git::DEFAULT_PERM_READ),
            $this->access_rights_presenter_options_builder->getDefaultOptions($project, Git::DEFAULT_PERM_WRITE),
            $this->access_rights_presenter_options_builder->getDefaultOptions($project, Git::DEFAULT_PERM_WPLUS),
            $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project),
            true,
            $this->getDefaultFineGrainedPermissionsRepresentations(
                $project,
                $this->default_fine_grained_permission_factory->getBranchesFineGrainedPermissionsForProject($project)
            ),
            $this->getDefaultFineGrainedPermissionsRepresentations(
                $project,
                $this->default_fine_grained_permission_factory->getTagsFineGrainedPermissionsForProject($project)
            ),
            $this->access_rights_presenter_options_builder->getAllOptions($project),
            $this->getDeleteUrl($repository, $project),
            $this->getCSRFToken($project),
            true,
            $this->regexp_fine_grained_retriever->areRegexpActivatedAtSiteLevel(),
            $this->regexp_fine_grained_retriever->areRegexpActivatedForDefault($project),
            [],
        );
    }

    private function canUserSetFineGrainedPermissions(
        GitRepository $repository,
        Project $project,
        PFUser $user,
        bool $is_forking_repository,
    ): bool {
        return ($this->git_permissions_manager->userIsGitAdmin($user, $project) ||
            $is_forking_repository ||
            $repository->belongsTo($user));
    }

    /**
     * @param list<FineGrainedPermission> $fine_grained_permissions
     */
    private function getFineGrainedPermissionsRepresentations(Project $project, array $fine_grained_permissions): array
    {
        $tags_permissions_representation = [];
        foreach ($fine_grained_permissions as $permission) {
            $tags_permissions_representation[] = $this->fine_grained_representation_builder->buildRepositoryPermission(
                $permission,
                $project
            );
        }
        return $tags_permissions_representation;
    }

    /**
     * @param list<DefaultFineGrainedPermission> $default_fine_grained_permissions
     */
    private function getDefaultFineGrainedPermissionsRepresentations(Project $project, array $default_fine_grained_permissions): array
    {
        $tags_permissions_representation = [];
        foreach ($default_fine_grained_permissions as $permission) {
            $tags_permissions_representation[] = $this->fine_grained_representation_builder->buildDefaultPermission(
                $permission,
                $project
            );
        }
        return $tags_permissions_representation;
    }

    private function getWarningContentForRegexpDisableModal(GitRepository $repository): array
    {
        $warning = [];
        if ($this->regexp_fine_grained_retriever->areRegexpRepositoryConflitingWithPlateform($repository)) {
            $warning[]['message'] = dgettext('tuleap-git', 'The regular expressions option has been disabled at plateform level.');
            $warning[]['message'] = dgettext('tuleap-git', 'All rules containing regular expressions will be deleted and you won\'t be able to activate the option again. If you don\'t save your modifications, the current state will still work.');
            $warning[]['message'] = dgettext('tuleap-git', 'Please confirm your action.');
        } else {
            $warning[]['message'] = dgettext('tuleap-git', 'All rules containing regular expressions will be deleted. Please confirm the desactivation of regular expressions.');
        }
        return $warning;
    }

    protected function getDeleteUrl(GitRepository $repository, Project $project): string
    {
        return '?' . http_build_query([
            'action' => 'delete-permissions',
            'pane' => 'perms',
            'repo_id' => $repository->getID(),
            'group_id' => $project->getID(),
        ]);
    }

    protected function getCSRFToken(Project $project): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken('?' . http_build_query([
            'action' => 'repo_management',
            'pane' => 'perms',
            'group_id' => $project->getID(),
        ]));
    }
}
