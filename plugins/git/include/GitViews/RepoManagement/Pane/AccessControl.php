<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_ProjectCreatorStatusDao;
use GitRepository;
use TemplateRendererFactory;
use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use PermissionsManager;
use Tuleap\Git\GitAccessControlPresenterBuilder;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use UserGroupDao;
use Codendi_Request;
use User_ForgeUserGroupFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use GitPermissionsManager;
use UserManager;

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
        RegexpFineGrainedRetriever $regexp_retriever,
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
    #[\Override]
    public function getIdentifier()
    {
        return 'perms';
    }

    /**
     * @see GitViews_RepoManagement_Pane::getTitle()
     */
    #[\Override]
    public function getTitle()
    {
        return dgettext('tuleap-git', 'Access control');
    }

    /**
     * @see GitViews_RepoManagement_Pane::getContent()
     */
    #[\Override]
    public function getContent()
    {
        $access_control_presenter_builder = new GitAccessControlPresenterBuilder(
            $this->getAccessRightsPresenterOptionsBuilder(),
            $this->default_fine_grained_factory,
            $this->fine_grained_retriever,
            $this->fine_grained_builder,
            $this->fine_grained_permission_factory,
            new Git_Driver_Gerrit_ProjectCreatorStatus(
                new Git_Driver_Gerrit_ProjectCreatorStatusDao()
            ),
            $this->git_permission_manager,
            $this->regexp_retriever,
        );

        $are_regexp_enabled     = $this->regexp_retriever->areRegexpActivatedForRepository($this->repository);
        $are_regexp_conflicting = $this->regexp_retriever->areRegexpRepositoryConflitingWithPlateform(
            $this->repository
        );
        $renderer               = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates/');
        return $renderer->renderToString(
            'repository-access-control',
            [
                'access_control_presenter' => $access_control_presenter_builder->buildForPermissionsManagement(
                    $this->repository,
                    $this->repository->getProject(),
                    UserManager::instance()->getCurrentUser()
                ),
                'csrf_token' => $this->csrf_token(),
                'repository_id' => $this->repository->getId(),
                'project_id' => $this->repository->getProjectId(),
                'are_regexp_enabled' => $are_regexp_enabled,
                'are_regexp_conflicting' => $are_regexp_conflicting,
            ],
        );
    }

    private function getAccessRightsPresenterOptionsBuilder()
    {
        $dao                = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($dao);

        return new AccessRightsPresenterOptionsBuilder($user_group_factory, PermissionsManager::instance());
    }

    #[\Override]
    public function getJavascriptViteAssets(): array
    {
        return [
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../../scripts/access-control/frontend-assets',
                    '/assets/git/access-control'
                ),
                'src/main.ts',
            ),
        ];
    }
}
