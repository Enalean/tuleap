<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\Asynchronous;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\ProjectAccessSuspendedException;

final class GitRepositoryRetriever implements RetrieveGitRepository
{
    public function __construct(
        private \GitRepositoryFactory $repository_factory,
        private CheckProjectAccess $project_access_checker,
    ) {
    }

    public function getRepository(int $repository_id, \PFUser $user): Ok|Err
    {
        $repository = $this->repository_factory->getRepositoryById($repository_id);
        if (! $repository) {
            return Result::err(Fault::fromMessage('Could not find repository with id #' . $repository_id));
        }
        if (! $repository->userCanRead($user)) {
            return Result::err(Fault::fromMessage('Could not find repository with id #' . $repository_id));
        }

        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $repository->getProject());
        } catch (
            \Project_AccessProjectNotFoundException
            | ProjectAccessSuspendedException
            | \Project_AccessDeletedException
            | \Project_AccessRestrictedException
            | \Project_AccessPrivateException $e
        ) {
            return Result::err(
                Fault::fromThrowableWithMessage(
                    $e,
                    sprintf('Could not find repository with id #%d: %s', $repository_id, $e->getMessage())
                )
            );
        }
        return Result::ok($repository);
    }
}
