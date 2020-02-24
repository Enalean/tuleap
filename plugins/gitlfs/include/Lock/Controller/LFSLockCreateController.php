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
use Tuleap\GitLFS\Lock\Request\LockCreateRequest;
use Tuleap\GitLFS\Lock\LockCreator;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
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
     * @var Prometheus
     */
    private $prometheus;

    public function __construct(
        \gitlfsPlugin $plugin,
        \GitRepositoryFactory $repository_factory,
        LFSAPIHTTPAccessControl $api_access_control,
        LockResponseBuilder $lock_response_builder,
        LockCreator $lock_creator,
        LockRetriever $lock_retriever,
        UserRetriever $user_retriever,
        Prometheus $prometheus
    ) {
        $this->plugin                = $plugin;
        $this->repository_factory    = $repository_factory;
        $this->api_access_control    = $api_access_control;
        $this->lock_response_builder = $lock_response_builder;
        $this->lock_creator          = $lock_creator;
        $this->lock_retriever        = $lock_retriever;
        $this->user_retriever        = $user_retriever;
        $this->prometheus            = $prometheus;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('gitlfs');

        $repository = $this->repository_factory->getByProjectNameAndPath(
            $variables['project_name'],
            $variables['path']
        );
        if ($repository === null || ! $repository->isCreated() ||
            ! $repository->getProject()->isActive() || ! $this->plugin->isAllowed($repository->getProject()->getID())) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        $json_string = file_get_contents('php://input');
        if ($json_string === false) {
            throw new \RuntimeException('Can not read the body of the request');
        }
        $lock_create_request = LockCreateRequest::buildFromJSONString($json_string);

        $user = $this->user_retriever->retrieveUser($request, $repository, $lock_create_request);

        $user_can_access = $this->api_access_control->canAccess(
            $repository,
            $lock_create_request,
            $user
        );
        if (! $user_can_access || $user === null) {
            throw new ForbiddenException();
        }

        $this->createLock($lock_create_request, $repository, $user);
    }

    private function createLock(LockCreateRequest $lock_create_request, \GitRepository $repository, \PFUser $user) : void
    {
        $locks = $this->lock_retriever->retrieveLocks(
            null,
            $lock_create_request->getPath(),
            null,
            null,
            $repository
        );

        if (! empty($locks)) {
            http_response_code(409);
            echo json_encode($this->lock_response_builder->buildLockConflictErrorResponse(array_shift($locks)));
            return;
        }

        $lock = $this->lock_creator->createLock(
            $lock_create_request->getPath(),
            $user,
            $lock_create_request->getReference() === null ? null : $lock_create_request->getReference()->getName(),
            $repository,
            new DateTimeImmutable()
        );

        $this->prometheus->increment('gitlfs_created_locks_total', 'Total number of created Git LFS locks');
        $response = $this->lock_response_builder->buildSuccessfulLockCreation($lock);

        echo json_encode($response);
    }
}
