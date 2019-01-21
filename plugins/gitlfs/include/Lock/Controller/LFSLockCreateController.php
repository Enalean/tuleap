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

use DateTimeImmutable;
use HTTPRequest;
use Tuleap\GitLFS\HTTP\LFSAPIHTTPAccessControl;
use Tuleap\GitLFS\HTTP\UserRetriever;
use Tuleap\GitLFS\Lock\LockRetriever;
use Tuleap\GitLFS\Lock\Response\LockResponseBuilder;
use Tuleap\GitLFS\Lock\Request\IncorrectlyFormattedReferenceRequestException;
use Tuleap\GitLFS\Lock\Request\LockCreateRequest;
use Tuleap\GitLFS\Lock\LockCreator;
use Tuleap\GitLFS\SSHAuthenticate\Authorization\TokenVerifier;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;

class LFSLockCreateController implements DispatchableWithRequestNoAuthz
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
     * @var \GitRepository
     */
    private $repository;

    /**
     * @var LockCreateRequest
     */
    private $lock_create_request;

    /**
     * @var LockCreator
     */
    private $lock_creator;

    /**
     * @var LockRetriever
     */
    private $lock_retriever;

    /**
     * @var UserRetriever
     */
    private $user_retriever;

    /**
     * @var \PFUser
     */
    private $user;

    public function __construct(
        \gitlfsPlugin $plugin,
        \GitRepositoryFactory $repository_factory,
        LFSAPIHTTPAccessControl $api_access_control,
        LockResponseBuilder $lock_response_builder,
        LockCreator $lock_creator,
        LockRetriever $lock_retriever,
        UserRetriever $user_retriever
    ) {
        $this->plugin                = $plugin;
        $this->repository_factory    = $repository_factory;
        $this->api_access_control    = $api_access_control;
        $this->lock_response_builder = $lock_response_builder;
        $this->lock_creator          = $lock_creator;
        $this->lock_retriever        = $lock_retriever;
        $this->user_retriever        = $user_retriever;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if ($this->user === null) {
            throw new \LogicException();
        }

        $locks = $this->lock_retriever->retrieveLocks(
            null,
            $this->lock_create_request->getPath(),
            null,
            null
        );

        if (! empty($locks)) {
            http_response_code(409);
            echo json_encode($this->lock_response_builder->buildLockConflictErrorResponse(array_shift($locks)));
            return;
        }

        $lock = $this->lock_creator->createLock(
            $this->lock_create_request->getPath(),
            $this->user,
            $this->lock_create_request->getReference() === null ? null : $this->lock_create_request->getReference()->getName(),
            $this->repository,
            new DateTimeImmutable()
        );

        $response = $this->lock_response_builder->buildSuccessfulLockCreation($lock);

        echo json_encode($response);
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');
        if ($this->repository !== null || $this->lock_create_request !== null || $this->user !== null) {
            throw new \RuntimeException(
                'This controller expects to process only one request and then thrown away. One request seems to already have been processed.'
            );
        }

        $this->repository = $this->repository_factory->getByProjectNameAndPath(
            $variables['project_name'],
            $variables['path']
        );
        $project = $this->repository->getProject();
        if ($this->repository === null || ! $project->isActive() || ! $this->plugin->isAllowed($project->getID()) ||
            ! $this->repository->isCreated()) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        $json_string = file_get_contents('php://input');
        if ($json_string === false) {
            throw new \RuntimeException('Can not read the body of the request');
        }
        try {
            $this->lock_create_request = LockCreateRequest::buildFromJSONString($json_string);
        } catch (IncorrectlyFormattedReferenceRequestException $exception) {
            throw new \RuntimeException($exception->getMessage(), 400);
        }

        $this->user = $this->user_retriever->retrieveUser($request, $url_verification, $this->repository, $this->lock_create_request);

        return $this->api_access_control->canAccess(
            $this->repository,
            $this->lock_create_request,
            $this->user
        );
    }
}
