<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\Tests\Stub;

use Git_Backend_Interface;
use GitRepository;
use PFUser;
use Project;

final class GitBackendInterfaceStub implements Git_Backend_Interface
{
    /**
     * @var array<int, list<PFUser>>
     */
    private array $users_with_read_permission;

    private function __construct()
    {
    }

    /**
     * @no-named-arguments
     */
    public function withUsersWhoHaveReadPermission(GitRepository $repository, PFUser ...$users): self
    {
        $this->users_with_read_permission[$repository->getId()] = $users;
        return $this;
    }

    public static function build(): self
    {
        return new self();
    }

    #[\Override]
    public function isInitialized(GitRepository $repository)
    {
        throw new \Exception('GitBackendInterfaceStub::isInitialized() called while not implemented.');
    }

    #[\Override]
    public function isCreated(GitRepository $repository)
    {
        throw new \Exception('GitBackendInterfaceStub::isCreated( called while not implemented.');
    }

    #[\Override]
    public function getAccessURL(GitRepository $repository): array
    {
        throw new \Exception('GitBackendInterfaceStub::getAccessURL() called while not implemented.');
    }

    #[\Override]
    public function getGitRootPath(): string
    {
        throw new \Exception('GitBackendInterfaceStub::getGitRootPath( called while not implemented.');
    }

    #[\Override]
    public function isNameAvailable(string $newName): bool
    {
        throw new \Exception('GitBackendInterfaceStub::isNameAvailable() called while not implemented.');
    }

    #[\Override]
    public function save(GitRepository $repository): bool
    {
        throw new \Exception('GitBackendInterfaceStub::save( called while not implemented.');
    }

    #[\Override]
    public function userCanRead(PFUser $user, GitRepository $repository): bool
    {
        if (! isset($this->users_with_read_permission[$repository->getId()])) {
            return false;
        }

        return array_find(
            $this->users_with_read_permission[$repository->getId()],
            fn(PFUser $user_with_read_permission) => $user_with_read_permission === $user
        ) !== null;
    }

    #[\Override]
    public function changeRepositoryMailingList(GitRepository $repository): bool
    {
        throw new \Exception('GitBackendInterfaceStub::changeRepositoryMailingList( called while not implemented.');
    }

    #[\Override]
    public function changeRepositoryMailPrefix(GitRepository $repository): bool
    {
        throw new \Exception('GitBackendInterfaceStub::changeRepositoryMailPrefix() called while not implemented.');
    }

    #[\Override]
    public function renameProject(Project $project, string $newName): bool
    {
        throw new \Exception('GitBackendInterfaceStub::renameProject( called while not implemented.');
    }

    #[\Override]
    public function canBeDeleted(GitRepository $repository): bool
    {
        throw new \Exception('GitBackendInterfaceStub::canBeDeleted() called while not implemented.');
    }

    #[\Override]
    public function markAsDeleted(GitRepository $repository): void
    {
        throw new \Exception('GitBackendInterfaceStub::markAsDeleted( called while not implemented.');
    }

    #[\Override]
    public function delete(GitRepository $repository): void
    {
        throw new \Exception('GitBackendInterfaceStub::delete() called while not implemented.');
    }

    #[\Override]
    public function deleteArchivedRepository(GitRepository $repository): void
    {
        throw new \Exception('GitBackendInterfaceStub::deleteArchivedRepository( called while not implemented.');
    }

    #[\Override]
    public function archiveBeforePurge(GitRepository $repository): bool
    {
        throw new \Exception('GitBackendInterfaceStub::archiveBeforePurge() called while not implemented.');
    }
}
