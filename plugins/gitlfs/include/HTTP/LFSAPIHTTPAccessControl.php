<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use PFUser;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Request\NotFoundException;

class LFSAPIHTTPAccessControl
{
    /**
     * @var AccessControlVerifier
     */
    private $access_control_verifier;

    public function __construct(AccessControlVerifier $access_control_verifier)
    {
        $this->access_control_verifier = $access_control_verifier;
    }

    /**
     * @throws NotFoundException
     * @return bool
     */
    public function canAccess(GitRepository $repository, GitLfsHTTPOperation $lfs_request, ?PFUser $user)
    {
        if ($user === null) {
            return true;
        }
        return $this->doesUserHaveAuthorization($user, $repository, $lfs_request);
    }

    /**
     * @return bool
     * @throws NotFoundException
     */
    private function doesUserHaveAuthorization(\PFUser $user, GitRepository $repository, GitLfsHTTPOperation $lfs_request)
    {
        if (! $repository->userCanRead($user)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Repository does not exist'));
        }

        if ($lfs_request->isWrite()) {
            $reference      = $lfs_request->getReference();
            $reference_name = '';
            if ($reference !== null) {
                $reference_name = $reference->getName();
            }
            return $this->access_control_verifier->canWrite($user, $repository, $reference_name);
        }

        return $lfs_request->isRead();
    }
}
