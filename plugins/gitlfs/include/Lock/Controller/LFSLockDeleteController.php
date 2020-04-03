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
use Tuleap\GitLFS\Lock\LockDestructionNotAuthorizedException;
use Tuleap\GitLFS\Lock\LockDestructor;
use Tuleap\GitLFS\Lock\LockRetriever;
use Tuleap\GitLFS\Lock\Request\LockDeleteRequest;
use Tuleap\GitLFS\Lock\Response\LockResponseBuilder;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LFSLockDeleteController implements DispatchableWithRequestNoAuthz
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
     * @var LockDestructor
     */
    private $lock_destructor;

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
        LockDestructor $lock_destructor,
        LockRetriever $lock_retriever,
        UserRetriever $user_retriever,
        Prometheus $prometheus
    ) {
        $this->plugin                = $plugin;
        $this->repository_factory    = $repository_factory;
        $this->api_access_control    = $api_access_control;
        $this->lock_response_builder = $lock_response_builder;
        $this->lock_destructor       = $lock_destructor;
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
        $lock_delete_request = LockDeleteRequest::buildFromJSONString($json_string);

        $user = $this->user_retriever->retrieveUser($request, $repository, $lock_delete_request);

        $user_can_access = $this->api_access_control->canAccess(
            $repository,
            $lock_delete_request,
            $user
        );
        if (! $user_can_access || $user === null) {
            throw new ForbiddenException();
        }

        $this->deleteLock((int) $variables['lock_id'], $lock_delete_request, $repository, $user);
    }

    private function deleteLock(
        int $lock_id,
        LockDeleteRequest $lock_delete_request,
        \GitRepository $repository,
        \PFUser $user
    ): void {
        $locks = $this->lock_retriever->retrieveLocks(
            $lock_id,
            null,
            null,
            null,
            $repository
        );

        if (empty($locks)) {
            http_response_code(404);
            echo json_encode($this->lock_response_builder->buildErrorResponse("Lock not found"));
            return;
        }

        $lock = array_shift($locks);

        try {
            if ($lock_delete_request->getForce()) {
                $this->lock_destructor->forceDeleteLock(
                    $lock
                );
            } else {
                $this->lock_destructor->deleteLock(
                    $lock,
                    $user
                );
            }
        } catch (LockDestructionNotAuthorizedException $exception) {
            http_response_code(403);
            echo json_encode($this->lock_response_builder->buildErrorResponse("You are not allowed to delete lock"));
            return;
        }

        $this->prometheus->increment('gitlfs_deleted_locks_total', 'Total number of deleted Git LFS locks');
        $response = $this->lock_response_builder->buildSuccessfulLockDestruction($lock);

        echo json_encode($response);
    }
}
