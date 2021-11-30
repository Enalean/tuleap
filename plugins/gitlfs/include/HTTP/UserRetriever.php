<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\HTTP;

use GitRepository;
use HTTPRequest;
use PFUser;
use Tuleap\Git\HTTP\HTTPAccessControl;
use Tuleap\Request\NotFoundException;
use UserManager;

class UserRetriever
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var HTTPAccessControl
     */
    private $http_access_control;

    /**
     * @var LSFAPIHTTPAuthorization
     */
    private $lfs_http_api_authorization;

    public function __construct(
        LSFAPIHTTPAuthorization $lfs_http_api_authorization,
        HTTPAccessControl $http_access_control,
        UserManager $user_manager,
    ) {
        $this->lfs_http_api_authorization = $lfs_http_api_authorization;
        $this->http_access_control        = $http_access_control;
        $this->user_manager               = $user_manager;
    }

    /**
     * @throws NotFoundException
     */
    public function retrieveUser(
        HTTPRequest $request,
        GitRepository $repository,
        GitLfsHTTPOperation $lfs_request,
    ): ?PFUser {
        $user = $this->lfs_http_api_authorization->getUserFromAuthorizationToken($request, $repository, $lfs_request);
        if ($user === null) {
            $user = $this->getUserFromGitHTTPAccessControlOrStop($repository, $lfs_request);
        }

        return $user;
    }

    /**
     * @throws NotFoundException
     */
    private function getUserFromGitHTTPAccessControlOrStop(
        GitRepository $repository,
        GitLfsHTTPOperation $lfs_request,
    ): ?PFUser {
        $pfo_user = $this->http_access_control->getUser($repository, $lfs_request);
        if ($pfo_user === null) {
            return null;
        }
        $user = $this->user_manager->getUserByUserName($pfo_user->getUnixName());
        if ($user === null) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }
        return $user;
    }
}
