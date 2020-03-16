<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

namespace Tuleap\Git\GitViews\RepoManagement\Pane;

use GitRepository;
use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use GitForkPermissionsManager;
use PermissionsManager;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use UserGroupDao;
use Codendi_Request;
use User_ForgeUserGroupFactory;
use Git_Backend_Gitolite;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use GitPermissionsManager;

class AccessControl extends Pane
{

    /**
     * @var GitPermissionsManager
     */
    private $git_permission_manager;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_factory;

    /**
     * @var FineGrainedRepresentationBuilder
     */
    private $fine_grained_builder;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var FineGrainedPermissionFactory
     */
    private $fine_grained_permission_factory;
    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        FineGrainedPermissionFactory $fine_grained_permission_factory,
        FineGrainedRetriever $fine_grained_retriever,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        DefaultFineGrainedPermissionFactory $default_fine_grained_factory,
        GitPermissionsManager $git_permission_manager,
        RegexpFineGrainedRetriever $regexp_retriever
    ) {
        parent::__construct($repository, $request);

        $this->fine_grained_permission_factory = $fine_grained_permission_factory;
        $this->fine_grained_retriever          = $fine_grained_retriever;
        $this->fine_grained_builder            = $fine_grained_builder;
        $this->default_fine_grained_factory    = $default_fine_grained_factory;
        $this->git_permission_manager          = $git_permission_manager;
        $this->regexp_retriever                = $regexp_retriever;
    }

    /**
     * @see GitViews_RepoManagement_Pane::getIdentifier()
     */
    public function getIdentifier()
    {
        return 'perms';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    public function getTitle()
    {
        return dgettext('tuleap-git', 'Access control');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    public function getContent()
    {
        $html = '';
        $html .= '<h2>' . $this->getTitle() . '</h2>';
        $html .= '<form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=' .
            $this->repository->getProjectId() . '">';
        $html .= '<input type="hidden" id="action" name="action" value="edit" />';
        $html .= '<input type="hidden" name="pane" value="' . $this->getIdentifier() . '" />';
        $html .= '<input type="hidden" id="repo_id" name="repo_id" value="' . $this->repository->getId() . '" />';
        if ($this->repository->getBackend() instanceof Git_Backend_Gitolite) {
            $html .= $this->accessControlGitolite();
        } else {
            $html .= $this->accessControl();
        }

        $are_regexp_enabled     = (bool) $this->regexp_retriever->areRegexpActivatedForRepository($this->repository);
        $are_regexp_conflicting = (bool) $this->regexp_retriever->areRegexpRepositoryConflitingWithPlateform(
            $this->repository
        );

        $html .= '<p><input type="submit" name="save" data-are-regexp-enabled="' . $are_regexp_enabled . '"
                data-are-regexp-confliting="' . $are_regexp_conflicting . '"
                class="btn btn-primary save-permissions-with-regexp" value="' .
            dgettext('tuleap-git', 'Save permissions') . '" /></p>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Display access control management for gitshell backend
     *
     * @return void
     */
    private function accessControl()
    {
        $html    = '';
        $public  = '';
        $private = '';
        $checked = 'checked="checked"';
        if ($this->repository->getAccess() == GitRepository::PRIVATE_ACCESS) {
            $private = $checked;
            $html .= '<input type="hidden" id="action" name="action" value="edit" />';
        } elseif ($this->repository->getAccess() == GitRepository::PUBLIC_ACCESS) {
            $public  = $checked;
            $html .= '<input type="hidden" id="action" name="action" value="confirm_private" />';
        }
        $html .= '<p id="plugin_git_access">';
        $html .= dgettext('tuleap-git', 'Access');
        $html .= ': <span><input type="radio" name="repo_access" value="private" ' . $private . '/> ';
        $html .= dgettext('tuleap-git', 'Private');
        $html .= '<input type="radio" name="repo_access" value="public" ' . $public . '/> Public';
        $html .= '</span>';
        $html .= '</p>';

        return $html;
    }

    /**
     * Display access control management for gitolite backend
     *
     * @return void
     */
    private function accessControlGitolite()
    {
        $forkPermissionsManager = new GitForkPermissionsManager(
            $this->repository,
            $this->getAccessRightsPresenterOptionsBuilder(),
            $this->fine_grained_retriever,
            $this->fine_grained_permission_factory,
            $this->fine_grained_builder,
            $this->default_fine_grained_factory,
            $this->git_permission_manager,
            $this->regexp_retriever
        );

        return $forkPermissionsManager->displayAccessControl();
    }

    private function getAccessRightsPresenterOptionsBuilder()
    {
        $dao                = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($dao);

        return new AccessRightsPresenterOptionsBuilder($user_group_factory, PermissionsManager::instance());
    }
}
