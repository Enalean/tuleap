<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

interface Git_Backend_Interface {
    const GIT_ROOT_PATH = '/var/lib/codendi/gitroot/';

    /**
     * Verify if the repository as already some content within
     *
     * @see    plugins/git/include/Git_Backend_Interface::isInitialized()
     * @param  GitRepository $repository
     * @return Boolean
     */
    public function isInitialized(GitRepository $respository);

    /**
     * Verify if the repository has been created
     *
     * @see    plugins/git/include/Git_Backend_Interface::isCreated()
     * @param  GitRepository $repository
     * @return Boolean
     */
    public function isCreated(GitRepository $respository);

    /**
     * Return URL to access the respository for remote git commands
     *
     * @param  GitRepository $repository
     * @return String
     */
    public function getAccessURL(GitRepository $repository);

    /**
     * Return the base root of all git repositories
     *
     * @return String
     */
    public function getGitRootPath();

    /**
     * Verify if given name is not already reserved on filesystem
     * 
     * @return bool
     */
    public function isNameAvailable($newName);

    /**
     * Save the repository
     *
     * @param GitRepository $repository
     *
     * @return bool
     */
    public function save($repository);

    /**
     * Test is user can read the content of this repository and metadata
     *
     * @param PFUser          $user       The user to test
     * @param GitRepository $repository The repository to test
     *
     * @return Boolean
     */
    public function userCanRead($user, $repository);

    /**
     * Update list of people notified by post-receive-email hook
     *
     * @param GitRepository $repository
     * 
     * @return Boolean
     */
    public function changeRepositoryMailingList($repository);

    /**
     * Change post-receive-email hook mail prefix
     *
     * @param GitRepository $repository
     * 
     * @return Boolean
     */
    public function changeRepositoryMailPrefix($repository);

    /**
     * Rename a project
     *
     * @param Project $project The project to rename
     * @param string  $newName The new name of the project
     *
     * @return true if success, false otherwise
     */
    public function renameProject(Project $project, $newName);

    /**
     * Check if repository can be deleted
     *
     * @return Boolean
     */
    public function canBeDeleted(GitRepository $repository);

    /**
     * Perform logical deletion repository in DB
     *
     * @param GitRepository $repository
     */
    public function markAsDeleted(GitRepository $repository);

    /**
     * Physically delete a repository already marked for deletion
     *
     * @param GitRepository $repository
     */
    public function delete(GitRepository $repository);

}
?>
