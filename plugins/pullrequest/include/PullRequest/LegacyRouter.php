<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Codendi_Request;
use CSRFSynchronizerToken;
use Exception;
use Feedback;
use GitRepository;
use GitRepositoryFactory;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\PullRequest\Exception\PullRequestAlreadyExistsException;
use Tuleap\PullRequest\Exception\PullRequestAnonymousUserException;
use Tuleap\PullRequest\Exception\PullRequestCannotBeCreatedException;
use Tuleap\PullRequest\Exception\PullRequestRepositoryMigratedOnGerritException;
use Tuleap\PullRequest\Exception\UnknownBranchNameException;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UserManager;

class LegacyRouter implements DispatchableWithRequest
{

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $git_repository_factory;

    /**
     * @var PullRequestCreator
     */
    private $pull_request_creator;

    public function __construct(
        PullRequestCreator $pull_request_creator,
        GitRepositoryFactory $git_repository_factory,
        UserManager $user_manager
    ) {
        $this->pull_request_creator   = $pull_request_creator;
        $this->git_repository_factory = $git_repository_factory;
        $this->user_manager           = $user_manager;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $repository_id = $request->get('repository_id');
        $project_id    = $request->get('group_id');

        $repository = $this->git_repository_factory->getRepositoryById($repository_id);

        if ($repository->getProjectId() != $project_id) {
            $this->redirectInRepositoryViewBecauseOfBadRequest($repository);
        }

        switch ($request->get('action')) {
            case 'generatePullRequest':
                $this->generatePullRequest($request, $repository, $project_id);
                break;
            default:
                $this->redirectInRepositoryViewBecauseOfBadRequest($repository);
                break;
        }
    }

    private function generatePullRequest(Codendi_Request $request, GitRepository $repository_src, $project_id)
    {
        $branch_src    = $request->get('branch_src');
        $branch_dest   = $request->get('branch_dest');
        list($repo_dest_id, $branch_dest)  = explode(':', $branch_dest, 2);

        $repository_id = $repository_src->getId();
        $user          = $this->user_manager->getCurrentUser();

        $token = new CSRFSynchronizerToken('/plugins/git/?action=view&repo_id=' . $repository_src->getId() . '&group_id=' . $project_id);
        $token->check();

        if (! $repository_src->userCanRead($user)) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_src,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'user_cannot_read_repository')
            );
        }

        $repository_dest = $this->git_repository_factory->getRepositoryById($repo_dest_id);

        try {
            $generated_pull_request = $this->pull_request_creator->generatePullRequest(
                $repository_src,
                $branch_src,
                $repository_dest,
                $branch_dest,
                $user
            );
        } catch (UnknownBranchNameException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_src,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'generate_pull_request_branch_error')
            );
        } catch (PullRequestCannotBeCreatedException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_src,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'pull_request_cannot_be_created')
            );
        } catch (PullRequestAnonymousUserException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_src,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'generate_pull_request_error')
            );
        } catch (PullRequestAlreadyExistsException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_src,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'pull_request_already_exists')
            );
        } catch (PullRequestRepositoryMigratedOnGerritException $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_src,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'repository_migrated_on_gerrit')
            );
        } catch (Exception $exception) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_src,
                $exception->getMessage()
            );
        }

        if (! $generated_pull_request) {
            $this->redirectInRepositoryViewWithErrorMessage(
                $repository_id,
                $GLOBALS['Language']->getText('plugin_pullrequest', 'generate_pull_request_error')
            );
        }

        $this->redirectToPullRequestViewIntoGitRepository($generated_pull_request, $project_id);
    }

    private function redirectInRepositoryViewWithErrorMessage(GitRepository $repository, $message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $message);

        $this->redirectInRepositoryView($repository);
    }

    private function redirectInRepositoryViewBecauseOfBadRequest(GitRepository $repository)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('plugin_pullrequest', 'invalid_request')
        );

        $this->redirectInRepositoryView($repository);
    }

    private function redirectInRepositoryView(GitRepository $repository)
    {
        $GLOBALS['Response']->redirect(
            "/plugins/git/".urlencode($repository->getProject()->getUnixNameLowerCase())."/".
            urlencode($repository->getFullName())."?" . http_build_query(
                array(
                    'action'   => "view",
                    'repo_id'  => $repository->getId(),
                    'group_id' => $repository->getProjectId()
                )
            )
        );
    }

    private function redirectToPullRequestViewIntoGitRepository(PullRequest $generated_pull_request, $project_id)
    {
        $repository_id            = $generated_pull_request->getRepositoryId();
        $generated_pull_request_id= $generated_pull_request->getId();

        $GLOBALS['Response']->redirect(
            "/plugins/git/?action=pull-requests&repo_id=$repository_id&group_id=$project_id#/pull-requests/$generated_pull_request_id"
        );
    }
}
