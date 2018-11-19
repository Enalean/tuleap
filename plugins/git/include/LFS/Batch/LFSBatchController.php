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

namespace Tuleap\Git\LFS\Batch;

use HTTPRequest;
use Tuleap\Git\HTTP\HTTPAccessControl;
use Tuleap\Git\LFS\Batch\Request\BatchRequest;
use Tuleap\Git\LFS\Batch\Request\IncorrectlyFormattedBatchRequestException;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;

class LFSBatchController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var HTTPAccessControl
     */
    private $http_access_control;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var AccessControlVerifier
     */
    private $access_control_verifier;
    /**
     * @var \Logger
     */
    private $logger;
    /**
     * @var \GitRepository
     */
    private $repository;
    /**
     * @var BatchRequest
     */
    private $batch_request;

    public function __construct(
        \GitRepositoryFactory $repository_factory,
        HTTPAccessControl $http_access_control,
        \UserManager $user_manager,
        AccessControlVerifier $access_control_verifier,
        \Logger $logger
    ) {
        $this->repository_factory      = $repository_factory;
        $this->http_access_control     = $http_access_control;
        $this->user_manager            = $user_manager;
        $this->access_control_verifier = $access_control_verifier;
        $this->logger                  = $logger;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $this->logger->debug(var_export($this->batch_request, true));
        http_response_code(501);
        echo json_encode(['message' => 'Processing of batch request is not yet implemented']);
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('git');
        if ($this->repository !== null || $this->batch_request !== null) {
            throw new \RuntimeException(
                'This controller expects to process only one request and then thrown away. One request seems to already have been processed.'
            );
        }

        $this->repository = $this->repository_factory->getByProjectNameAndPath(
            $variables['project_name'],
            $this->getRepoPathWithFinalDotGit($variables['path'])
        );
        if ($this->repository === null || ! $this->repository->getProject()->isActive() || ! $this->repository->isCreated()) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        $json_string = file_get_contents('php://input');
        if ($json_string === false) {
            throw new \RuntimeException('Can not read the body of the request');
        }
        try {
            $this->batch_request = BatchRequest::buildFromJSONString($json_string);
        } catch (IncorrectlyFormattedBatchRequestException $exception) {
            throw new \RuntimeException($exception->getMessage(), 400);
        }

        $pfo_user = $this->http_access_control->getUser($url_verification, $this->repository, $this->batch_request);
        if ($pfo_user === null) {
            return true;
        }

        $user = $this->user_manager->getUserByUserName($pfo_user->getUnixName());
        if ($user === null) {
            return false;
        }

        if ($this->batch_request->isRead()) {
            return $this->repository->userCanRead($user);
        }

        if ($this->batch_request->isWrite()) {
            $reference      = $this->batch_request->getReference();
            $reference_name = '';
            if ($reference !== null) {
                $reference_name = $reference->getName();
            }
            return $this->access_control_verifier->canWrite($user, $this->repository, $reference_name);
        }

        return false;
    }

    /**
     * @return string
     */
    private function getRepoPathWithFinalDotGit($path)
    {
        if (substr($path, strlen($path) - 4) !== '.git') {
            return $path.'.git';
        }
        return $path;
    }
}
