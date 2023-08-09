<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\PullRequest\REST\v1\Permissions;

use GitRepoNotFoundException;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Authorization\UserCannotMergePullRequestException;
use Tuleap\PullRequest\PullRequest;

class PullRequestIsMergeableChecker
{
    public function __construct(private readonly PullRequestPermissionChecker $permission_checker)
    {
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    public function checkUserCanMerge(PullRequest $pull_request, PFUser $user): void
    {
        try {
            $this->permission_checker->checkPullRequestIsMergeableByUser($pull_request, $user);
        } catch (UserCannotMergePullRequestException $e) {
            throw new RestException(403, 'User is not able to WRITE the git repository');
        } catch (GitRepoNotFoundException $e) {
            throw new RestException(404, 'Git repository not found');
        }
    }
}
