<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Lock\Controller;

use HTTPRequest;
use Tuleap\GitLFS\HTTP\LFSAPIHTTPAccessControl;
use Tuleap\GitLFS\HTTP\UserRetriever;
use Tuleap\GitLFS\Lock\LockRetriever;
use Tuleap\GitLFS\Lock\Request\LockVerifyRequest;
use Tuleap\GitLFS\Lock\Response\LockResponseBuilder;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LFSLockVerifyController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var \gitlfsPlugin
     */
    private $plugin;

    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var LFSAPIHTTPAccessControl
     */
    private $api_access_control;

    /**
     * @var LockResponseBuilder
     */
    private $lock_response_builder;

    /**
     * @var LockRetriever
     */
    private $lock_retriever;

    /**
     * @var UserRetriever
     */
    private $user_retriever;

    public function __construct(
        \gitlfsPlugin $plugin,
        \GitRepositoryFactory $repository_factory,
        LFSAPIHTTPAccessControl $api_access_control,
        LockResponseBuilder $lock_response_builder,
        LockRetriever $lock_retriever,
        UserRetriever $user_retriever
    ) {
        $this->plugin                = $plugin;
        $this->repository_factory    = $repository_factory;
        $this->api_access_control    = $api_access_control;
        $this->lock_response_builder = $lock_response_builder;
        $this->lock_retriever        = $lock_retriever;
        $this->user_retriever        = $user_retriever;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        $repository = $this->repository_factory->getByProjectNameAndPath(
            $variables['project_name'],
            $variables['path']
        );
        if (
            $repository === null || ! $repository->isCreated() ||
            ! $repository->getProject()->isActive() || ! $this->plugin->isAllowed($repository->getProject()->getID())
        ) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        $json_string = file_get_contents('php://input');
        if ($json_string === false) {
            throw new \RuntimeException('Can not read the body of the request');
        }
        $lock_verify_request = LockVerifyRequest::buildFromJSONString($json_string);

        $user = $this->user_retriever->retrieveUser($request, $repository, $lock_verify_request);

        $user_can_access = $this->api_access_control->canAccess(
            $repository,
            $lock_verify_request,
            $user
        );

        if (! $user_can_access || $user === null) {
            throw new ForbiddenException();
        }

        $this->displayLocks($lock_verify_request, $repository, $user);
    }

    private function displayLocks(LockVerifyRequest $lock_verify_request, \GitRepository $repository, \PFUser $user): void
    {
        $reference = $lock_verify_request->getReference() === null ? null : $lock_verify_request->getReference()->getName();

        $ours = $this->lock_retriever->retrieveLocks(
            null,
            null,
            $reference,
            $user,
            $repository
        );

        $theirs = $this->lock_retriever->retrieveLocksNotBelongingToOwner(
            $reference,
            $user,
            $repository
        );

        echo json_encode($this->lock_response_builder->buildSuccessfulLockVerify($ours, $theirs));
    }
}
