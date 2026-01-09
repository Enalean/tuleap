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
    /**
     * @param array<int, GitRepository> $git_repositories
     */
    private function __construct(private readonly array $git_repositories)
    {
    }

    #[\Override]
    public function getRepositoryById(int $id): ?GitRepository
    {
        return $this->git_repositories[$id] ?? null;
    }

    #[\Override]
    public function getRepositoryByIdUserCanSee(PFUser $user, int $id): GitRepository
    {
        if (! isset($this->git_repositories[$id])) {
            throw new GitRepoNotFoundException();
        }
        return $this->git_repositories[$id];
    }

    /**
     * @no-named-arguments
     */
    public static function withGitRepositories(GitRepository $first_repository, GitRepository ...$other_repositories): self
    {
        $repositories = [];
        foreach ([$first_repository, ...$other_repositories] as $repository) {
            $repositories[$repository->getId()] = $repository;
        }
        return new self($repositories);
    }

    public static function withoutGitRepository(): self
    {
        return new self([]);
    }
}
