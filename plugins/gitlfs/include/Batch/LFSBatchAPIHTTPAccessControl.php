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

namespace Tuleap\GitLFS\Batch;

use GitRepository;
use Tuleap\Git\HTTP\HTTPAccessControl;
use Tuleap\GitLFS\Batch\Request\BatchRequest;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Request\NotFoundException;

class LFSBatchAPIHTTPAccessControl
{
    /**
     * @var LFSBatchAPIHTTPAccessControl
     */
    private $lfs_http_api_authorization;
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

    public function __construct(
        LSFBatchAPIHTTPAuthorization $lfs_http_api_authorization,
        HTTPAccessControl $http_access_control,
        \UserManager $user_manager,
        AccessControlVerifier $access_control_verifier
    ) {
        $this->lfs_http_api_authorization = $lfs_http_api_authorization;
        $this->http_access_control        = $http_access_control;
        $this->user_manager               = $user_manager;
        $this->access_control_verifier    = $access_control_verifier;
    }

    /**
     * @throws NotFoundException
     * @return bool
     */
    public function canAccess(\HTTPRequest $request, \URLVerification $url_verification, GitRepository $repository, BatchRequest $batch_request)
    {
        $user = $this->lfs_http_api_authorization->getUserFromAuthorizationToken($request, $repository, $batch_request);
        if ($user === null) {
            $user = $this->getUserFromGitHTTPAccessControlOrStop($url_verification, $repository, $batch_request);
        }
        if ($user === null) {
            return true;
        }
        return $this->doesUserHasAuthorization($user, $repository, $batch_request);
    }

    /**
     * @return \PFUser|null
     */
    private function getUserFromGitHTTPAccessControlOrStop(
        \URLVerification $url_verification,
        GitRepository $repository,
        BatchRequest $batch_request
    ) {
        $pfo_user = $this->http_access_control->getUser($url_verification, $repository, $batch_request);
        if ($pfo_user === null) {
            return null;
        }
        $user = $this->user_manager->getUserByUserName($pfo_user->getUnixName());
        if ($user === null) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }
        return $user;
    }

    /**
     * @return bool
     * @throws NotFoundException
     */
    private function doesUserHasAuthorization(\PFUser $user, GitRepository $repository, BatchRequest $batch_request)
    {
        if (! $repository->userCanRead($user)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        if ($batch_request->isWrite()) {
            $reference      = $batch_request->getReference();
            $reference_name = '';
            if ($reference !== null) {
                $reference_name = $reference->getName();
            }
            return $this->access_control_verifier->canWrite($user, $repository, $reference_name);
        }

        return $batch_request->isRead();
    }
}
