<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * I'm responsible of helping svn hooks to find relevant data
 */
class SVN_Hooks
{
    /** @var ProjectManager */
    private $project_manager;

    /** @var UserManager */
    private $user_manager;

    public function __construct(ProjectManager $project_manager, UserManager $user_manager)
    {
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
    }

    /**
     * Returns the user that match the given string
     *
     * @param String $user_name
     * @return PFUser
     * @throws Exception
     */
    public function getUserByName($user_name)
    {
        $user = $this->user_manager->findUser($user_name);
        if ($user && $user->isAlive()) {
            return $user;
        }
        throw new Exception('Invalid user');
    }

    /**
     * Returns the Project that match the given string
     *
     * @param String $repository_path
     * @return Project
     * @throws Exception
     */
    public function getProjectFromRepositoryPath($repository_path)
    {
        $unix_group_name = substr($repository_path, strlen(ForgeConfig::get('svn_prefix')) + 1);
        $project = $this->project_manager->getProjectByUnixName($unix_group_name);
        if ($project && !$project->isError() && !$project->isDeleted()) {
            return $project;
        }
        throw new Exception('Invalid project');
    }

    /**
     * Return the commit message of the given transaction in the repository
     *
     * @param String $repository
     * @param String $txn
     * @return String
     */
    public function getMessageFromTransaction($repository, $txn)
    {
        return $this->getMessageFromSvnLook("-t '$txn' '$repository'");
    }

    /**
     * Return the commit message of the given revision in the repository
     *
     * @param String $repository
     * @param String $revision
     * @return String
     */
    public function getMessageFromRevision($repository, $revision)
    {
        return $this->getMessageFromSvnLook("'$repository' -r '$revision'");
    }

    private function getMessageFromSvnLook($parameters)
    {
        $logmsg = array();
        exec("/usr/bin/svnlook log $parameters", $logmsg);
        return implode("\n", $logmsg);
    }
}
