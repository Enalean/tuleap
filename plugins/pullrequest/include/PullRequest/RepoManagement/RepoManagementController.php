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

namespace Tuleap\PullRequest\RepoManagement;

use GitPermissionsManager;
use GitRepository;
use GitRepositoryFactory;
use HTTPRequest;
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\PullRequest\MergeSetting\MergeSettingDAO;
use Tuleap\Request\DispatchableWithRequest;

class RepoManagementController implements DispatchableWithRequest
{
    /**
     * @var MergeSettingDAO
     */
    private $merge_setting_dao;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        MergeSettingDAO $merge_setting_dao,
        GitRepositoryFactory $repository_factory,
        GitPermissionsManager $permissions_manager
    ) {
        $this->merge_setting_dao   = $merge_setting_dao;
        $this->repository_factory  = $repository_factory;
        $this->permissions_manager = $permissions_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getProject()->usesService(\gitPlugin::SERVICE_SHORTNAME)) {
            throw new \Tuleap\Request\NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        \Tuleap\Project\ServiceInstrumentation::increment('git');

        $repository = $this->getRepositoryFromRequest($request);

        $this->merge_setting_dao->save($repository->getId(), (int) $request->get('is_merge_commit_allowed'));
        $layout->addFeedback(\Feedback::INFO, dgettext("tuleap-pullrequest", "Pull requests settings updated"));
        $layout->redirect(
            GIT_BASE_URL . '/?' . http_build_query(
                [
                    'action'   => 'repo_management',
                    'group_id' => $request->getProject()->getID(),
                    'repo_id'  => $repository->getId(),
                    'pane'     => PullRequestPane::NAME
                ]
            )
        );
    }

    /**
     *
     * @return GitRepository
     * @throws \Tuleap\Request\ForbiddenException
     */
    protected function getRepositoryFromRequest(HTTPRequest $request)
    {
        $repository = $this->repository_factory->getRepositoryById($request->get('repository_id'));
        if (! $this->canUserAdministrateRepository($request->getCurrentUser(), $repository)) {
            throw new \Tuleap\Request\ForbiddenException();
        }

        if ((int) $repository->getProjectId() !== (int) $request->getProject()->getID()) {
            throw new \Tuleap\Request\ForbiddenException();
        }

        return $repository;
    }

    private function canUserAdministrateRepository(PFUser $user, GitRepository $repository)
    {
        return $this->permissions_manager->userIsGitAdmin($user, $repository->getProject()) ||
            $repository->belongsTo($user);
    }
}
