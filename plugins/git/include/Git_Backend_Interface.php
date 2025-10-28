<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

interface Git_Backend_Interface
{
    public const string GIT_ROOT_PATH = '/var/lib/codendi/gitroot/';

    /**
     * Verify if the repository as already some content within
     *
     * @see    plugins/git/include/Git_Backend_Interface::isInitialized()
     * @return bool
     */
    public function isInitialized(GitRepository $repository);

    /**
     * Verify if the repository has been created
     *
     * @see    plugins/git/include/Git_Backend_Interface::isCreated()
     * @return bool
     */
    public function isCreated(GitRepository $repository);

    /**
     * Return URL to access the repository for remote git commands
     *
     * @return array<string, string>
     */
    public function getAccessURL(GitRepository $repository): array;

    /**
     * Return the base root of all git repositories
     */
    public function getGitRootPath(): string;

    /**
     * Verify if given name is not already reserved on filesystem
     */
    public function isNameAvailable(string $newName): bool;

    /**
     * Save the repository
     */
    public function save(GitRepository $repository): bool;

    /**
     * Test is user can read the content of this repository and metadata
     */
    public function userCanRead(PFUser $user, GitRepository $repository): bool;

    /**
     * Update list of people notified by post-receive-email hook
     */
    public function changeRepositoryMailingList(GitRepository $repository): bool;

    /**
     * Change post-receive-email hook mail prefix
     */
    public function changeRepositoryMailPrefix(GitRepository $repository): bool;

    /**
     * Rename a project
     */
    public function renameProject(Project $project, string $newName): bool;

    /**
     * Check if repository can be deleted
     */
    public function canBeDeleted(GitRepository $repository): bool;

    /**
     * Perform logical deletion repository in DB
     *
     */
    public function markAsDeleted(GitRepository $repository): void;

    /**
     * Physically delete a repository already marked for deletion
     *
     */
    public function delete(GitRepository $repository): void;

    /**
     * Purge archived repository
     *
     */
    public function deleteArchivedRepository(GitRepository $repository): void;

    /**
     * Move the archived gitolite repositories to the archiving area before purge
     */
    public function archiveBeforePurge(GitRepository $repository): bool;
}
