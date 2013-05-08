<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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

class SVN_CommitMessage {

    /** @var ProjectManager */
    private $project_manager;

    /** @var ReferenceManager */
    private $reference_manager;

    public function __construct(ProjectManager $project_manager, ReferenceManager $reference_manager) {
        $this->project_manager   = $project_manager;
        $this->reference_manager = $reference_manager;
    }

    public function assertCanBeModified($repository, $action, $propname, $commit_message) {
        $this->propsetIsOnLog($action, $propname);
        $project = $this->getProjectFromRepositoryPath($repository);
        $this->commitMessageCanBeModified($project);
        $this->commitMessageIsValid($project, $commit_message);
    }

    private function propsetIsOnLog($action, $propname) {
        if (! ($action == 'M' && $propname == 'svn:log')) {
            throw new Exception('Cannot modify anything but svn:log');
        }
    }

    private function commitMessageCanBeModified(Project $project) {
        if (! $project->canChangeSVNLog()) {
            throw new Exception('Project forbid to change log messages');
        }
    }

    private function commitMessageIsValid(Project $project, $commit_message) {
        if ($project->isSVNMandatoryRef()) {
            // Marvelous, extractCrossRef depends on globals group_id to find the group
            // when it's not explicit... yeah!
            $GLOBALS['group_id'] = $project->getID();
            if (! $this->reference_manager->stringContainsReferences($commit_message, $project)) {
                throw new Exception('Commit message must contains references.');
            }
        }
    }

    private function getProjectFromRepositoryPath($repository_path) {
        $unix_group_name = substr($repository_path, strlen(Config::get('svn_prefix')) + 1);
        $project = $this->project_manager->getProjectByUnixName($unix_group_name);
        if ($project && !$project->isError() && !$project->isDeleted()) {
            return $project;
        }
        throw new Exception('Invalid project');
    }

}

class SVN_CommitMessageUpdate {

    /** @var ProjectManager */
    private $project_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var ReferenceManager */
    private $reference_manager;

    /** @var SvnCommitsDao */
    private $dao;

    public function __construct(ProjectManager $project_manager, UserManager $user_manager, ReferenceManager $reference_manager, SvnCommitsDao $dao) {
        $this->project_manager   = $project_manager;
        $this->user_manager      = $user_manager;
        $this->reference_manager = $reference_manager;
        $this->dao               = $dao;
    }

    public function update($repository_path, $revision, $user) {
        $project = $this->getProjectFromRepositoryPath($repository_path);
        $user    = $this->getUserByName($user);
        $message = $this->getMessageFromRevision($repository_path, $revision);
        $this->dao->updateCommitMessage($project->getID(), $revision, $message);
        // Marvelous, extractCrossRef depends on globals group_id to find the group
        // when it's not explicit... yeah!
        $GLOBALS['group_id'] = $project->getID();
        $this->reference_manager->extractCrossRef(
            $message,
            $revision,
            ReferenceManager::REFERENCE_NATURE_SVNREVISION,
            $project->getID(),
            $user->getId()
        );
    }

    private function getProjectFromRepositoryPath($repository_path) {
        $unix_group_name = substr($repository_path, strlen(Config::get('svn_prefix')) + 1);
        $project = $this->project_manager->getProjectByUnixName($unix_group_name);
        if ($project && !$project->isError() && !$project->isDeleted()) {
            return $project;
        }
        throw new Exception('Invalid project');
    }

    private function getUserByName($user_name) {
        $user = $this->user_manager->getUserByUserName($user_name);
        if ($user && $user->isAlive()) {
            return $user;
        }
        throw new Exception('Invalid user');
    }

    public function getMessageFromTransaction($repository, $txn) {
        return $this->getMessageFromSvnLook("-t '$txn' '$repository'");
    }

    public function getMessageFromRevision($repository, $revision) {
        return $this->getMessageFromSvnLook("'$repository' -r '$revision'");
    }

    private function getMessageFromSvnLook($parameters) {
        $logmsg = array();
        exec("/usr/bin/svnlook log $parameters", $logmsg);
        return implode("\n", $logmsg);
    }
}

?>
