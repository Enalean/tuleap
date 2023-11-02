<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\PullRequest\REST\v1;

use GitRepoNotFoundException;
use Luracast\Restler\RestException;
use PFUser;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use Tuleap\PullRequest\Authorization\CheckUserCanAccessPullRequest;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\REST\v1\FaultMapper;

final class AccessiblePullRequestRESTRetriever
{
    public function __construct(
        private readonly PullRequestRetriever $pull_request_factory,
        private readonly CheckUserCanAccessPullRequest $permission_checker,
    ) {
    }

    /**
     * @throws RestException
     */
    public function getAccessiblePullRequest(int $pull_request_id, PFUser $current_user): PullRequest
    {
        $pull_request = $this->pull_request_factory->getPullRequestById($pull_request_id);
        return $pull_request->match(
            function (PullRequest $pull_request) use ($current_user) {
                try {
                    $this->permission_checker->checkPullRequestIsReadableByUser($pull_request, $current_user);
                    return $pull_request;
                } catch (GitRepoNotFoundException | Project_AccessProjectNotFoundException $exception) {
                    throw new RestException(404);
                } catch (Project_AccessException $exception) {
                    throw new RestException(403, $exception->getMessage());
                } catch (UserCannotReadGitRepositoryException $exception) {
                    throw new RestException(403, 'User is not able to READ the git repository');
                }
            },
            FaultMapper::mapToRestException(...)
        );
    }
}
