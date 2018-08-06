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

namespace Tuleap\Git\DefaultSettings\Pane;

use CSRFSynchronizerToken;
use Git;
use GitPermissionsManager;
use Project;
use TemplateRendererFactory;
use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use UserManager;

class AccessControl extends Pane
{
    const NAME = 'access_control';
    /**
     * @var Project
     */
    private $project;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var AccessRightsPresenterOptionsBuilder
     */
    private $access_rights_builder;
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;
    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_permission_factory;
    /**
     * @var FineGrainedRepresentationBuilder
     */
    private $fine_grained_builder;
    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    public function __construct(
        AccessRightsPresenterOptionsBuilder $access_rights_builder,
        GitPermissionsManager $git_permissions_manager,
        FineGrainedRetriever $fine_grained_retriever,
        DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        RegexpFineGrainedRetriever $regexp_retriever,
        UserManager $user_manager,
        Project $project,
        $is_active
    ) {
        parent::__construct(
            $GLOBALS['Language']->getText('plugin_git', 'view_repo_access_control'),
            "?" . http_build_query(
                [
                    'action'   => 'admin-default-settings',
                    'group_id' => $project->getID(),
                    'pane'     => self::NAME
                ]
            ),
            $is_active,
            false
        );
        $this->project                                 = $project;
        $this->user_manager                            = $user_manager;
        $this->access_rights_builder                   = $access_rights_builder;
        $this->git_permissions_manager                 = $git_permissions_manager;
        $this->fine_grained_retriever                  = $fine_grained_retriever;
        $this->default_fine_grained_permission_factory = $default_fine_grained_permission_factory;
        $this->fine_grained_builder                    = $fine_grained_builder;
        $this->regexp_retriever                        = $regexp_retriever;
    }

    /**
     * @return string
     */
    public function content()
    {
        $project_id = $this->project->getID();

        $read_options   = $this->access_rights_builder->getDefaultOptions($this->project, Git::DEFAULT_PERM_READ);
        $write_options  = $this->access_rights_builder->getDefaultOptions($this->project, Git::DEFAULT_PERM_WRITE);
        $rewind_options = $this->access_rights_builder->getDefaultOptions($this->project, Git::DEFAULT_PERM_WPLUS);
        $csrf           = new CSRFSynchronizerToken(
            "/plugins/git/?" . http_build_query(
                [
                    'group_id' => $project_id,
                    'action'   => 'admin-default-access-rights'
                ]
            )
        );

        $user = $this->user_manager->getCurrentUser();

        $can_use_fine_grained_permissions = $this->git_permissions_manager->userIsGitAdmin($user, $this->project);

        $are_fine_grained_permissions_defined = $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions(
            $this->project
        );

        $branches_permissions     = $this->default_fine_grained_permission_factory->getBranchesFineGrainedPermissionsForProject(
            $this->project
        );
        $tags_permissions         = $this->default_fine_grained_permission_factory->getTagsFineGrainedPermissionsForProject(
            $this->project
        );
        $new_fine_grained_ugroups = $this->access_rights_builder->getAllOptions($this->project);

        $delete_url  = '?action=delete-default-permissions&pane=access_control&group_id=' . urlencode($this->project->getID());
        $url         = '?action=admin-default-settings&pane=access_control&group_id=' . urlencode($this->project->getID());
        $csrf_delete = new CSRFSynchronizerToken($url);

        $branches_permissions_representation = [];
        foreach ($branches_permissions as $permission) {
            $branches_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $this->project
            );
        }

        $tags_permissions_representation = [];
        foreach ($tags_permissions as $permission) {
            $tags_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $this->project
            );
        }

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        return $renderer->renderToString(
            'admin-git-access-rights',
            new AccessControlPresenter(
                $this->project,
                $csrf,
                $read_options,
                $write_options,
                $rewind_options,
                $are_fine_grained_permissions_defined,
                $can_use_fine_grained_permissions,
                $branches_permissions_representation,
                $tags_permissions_representation,
                $new_fine_grained_ugroups,
                $delete_url,
                $csrf_delete,
                $this->areRegexpActivatedAtSiteLevel(),
                $this->isRegexpActivatedForDefault(),
                $this->areRegexpConflictingForDefault()
            )
        );
    }

    private function areRegexpActivatedAtSiteLevel()
    {
        return $this->regexp_retriever->areRegexpActivatedAtSiteLevel();
    }

    private function isRegexpActivatedForDefault()
    {
        return $this->regexp_retriever->areRegexpActivatedForDefault($this->project);
    }

    private function areRegexpConflictingForDefault()
    {
        return $this->regexp_retriever->areDefaultRegexpConflitingWithPlateform($this->project);
    }
}
