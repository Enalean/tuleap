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

namespace Tuleap\Git\Tests\Stub;

use GitRepoNotFoundException;
use GitRepository;
use PFUser;
use Tuleap\Git\RetrieveGitRepository;

final class RetrieveGitRepositoryStub implements RetrieveGitRepository
{
    private function __construct(private readonly ?GitRepository $git_repository)
    {
    }

    public function getRepositoryById(int $id): ?GitRepository
    {
        return $this->git_repository;
    }

    /**
     * @throws GitRepoNotFoundException
     */
    public function getRepositoryByIdUserCanSee(PFUser $user, int $id): GitRepository
    {
        if (! $this->git_repository) {
            throw new GitRepoNotFoundException();
        }
        return $this->git_repository;
    }

    public static function withGitRepository(GitRepository $git_repository): self
    {
        return new self($git_repository);
    }

    public static function withoutGitRepository(): self
    {
        return new self(null);
    }
}
