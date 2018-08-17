<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

require_once 'common/backend/BackendLogger.class.php';

/**
 * I know how to speak to a Gerrit 2.5 remote server
 */
class Git_Driver_GerritLegacy implements Git_Driver_Gerrit {
    const INDEX_GROUPS_VERBOSE_NAME = 0;
    const INDEX_GROUPS_VERBOSE_UUID = 1;

    const COMMAND      = 'gerrit';
    const GSQL_COMMAND = 'gerrit gsql --format json -c';
    const EXIT_CODE    = 1;

    /** @var Git_Driver_Gerrit_RemoteSSHCommand */
    private $ssh;

    /** @var Logger */
    private $logger;

    public function __construct(Git_Driver_Gerrit_RemoteSSHCommand $ssh, Logger $logger) {
        $this->ssh    = $ssh;
        $this->logger = $logger;
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param GitRepository $repository
     * @param String $parent_project_name
     * @return String Gerrit project name
     */
    public function createProject(Git_RemoteServer_GerritServer $server, GitRepository $repository, $parent_project_name) {
        $gerrit_project = $this->getGerritProjectName($repository);
        $command = implode(' ', array(self::COMMAND, 'create-project --parent', $parent_project_name, $gerrit_project));
        return $this->actionCreateProject($server, $command, $gerrit_project);
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param Project $project
     * @param String $admin_group_name
     * @return String Gerrit project name
     */
    public function createProjectWithPermissionsOnly(Git_RemoteServer_GerritServer $server, Project $project, $admin_group_name) {
        $project_parent_name = $project->getUnixName();
        $command = implode(' ', array(self::COMMAND, 'create-project --permissions-only', $project_parent_name, '--owner', $admin_group_name));
        return $this->actionCreateProject($server, $command, $project_parent_name);
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param string $command
     * @param string $project_name
     * @return string Gerrit project name
     * @throws Git_Driver_Gerrit_Exception
     * @throws Git_Driver_Gerrit_Exception
     */
    private function actionCreateProject(Git_RemoteServer_GerritServer $server, $command, $project_name) {
        try {
            $this->ssh->execute($server, $command);
            $this->logger->info("Gerrit: Project $project_name successfully initialized");
            return $project_name;
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            throw $this->computeException($e, $command);
        }
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param string $project_name
     * @return true if the gerrit project exists, else return false
     */
    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name) {
        return in_array($project_name, $this->listParentProjects($server));
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     * @param string $project_name
     * @return true if the gerrit project exists, else return false
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function doesTheProjectExist(Git_RemoteServer_GerritServer $server, $project_name) {
        return in_array($project_name, $this->listProjects($server));
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function ping(Git_RemoteServer_GerritServer $server) {
        $command = self::COMMAND . ' version';
        return explode(PHP_EOL, $this->ssh->execute($server, $command));
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     * @return array : the list of the projects created in the gerrit server
     */
    private function listProjects(Git_RemoteServer_GerritServer $server) {
        $command = self::COMMAND . ' ls-projects';
        return explode(PHP_EOL, $this->ssh->execute($server, $command));
    }

    /**
     * Should be protected (only used publicly in tests)?
     * @param Git_RemoteServer_GerritServer $server
     * @return array : the list of the parent project created in the gerrit server
     */
    public function listParentProjects(Git_RemoteServer_GerritServer $server) {
        $command = self::COMMAND . ' ls-projects --type PERMISSIONS';
        return explode(PHP_EOL, $this->ssh->execute($server, $command));
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param String $group_name
     * @param String $owner
     */
    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, $owner) {
        if ($this->doesTheGroupExist($server, $group_name)) {
            $this->logger->info("Gerrit: Group $group_name already exists on Gerrit");
            return;
        }

        $command = self::COMMAND . ' create-group ' . $group_name;
        if ($group_name != $owner) {
            $command .= ' --owner '.$owner;
        }

        try {
            $this->ssh->execute($server, $command);
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            throw $this->computeException($e, $command);
        }

        $this->logger->info("Gerrit: Group $group_name successfully created");
    }

    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name) {
        $json_result = $this->getAccountGroupsInfo($server, $group_full_name);
        if (isset($json_result->columns->group_uuid)) {
            return $json_result->columns->group_uuid;
        }
    }

    /**
     * Returns gerrit GROUP_ID for a user group
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param type $group_full_name
     *
     * @return type
     */
    public function getGroupId(Git_RemoteServer_GerritServer $server, $group_full_name) {
        $json_result = $this->getAccountGroupsInfo($server, $group_full_name);
        if (isset($json_result->columns->group_id)) {
            return $json_result->columns->group_id;
        }
    }

    private function getAccountGroupsInfo(Git_RemoteServer_GerritServer $server, $group_full_name)
    {
        $sql                     = "SELECT * FROM account_groups WHERE name='".$group_full_name."'";
        $command_result          = $this->executeQuery($server, $sql);
        $exploded_command_result = explode("\n", $command_result);
        return json_decode(array_shift($exploded_command_result));
    }

    public function doesTheGroupExist(Git_RemoteServer_GerritServer $server, $group_name) {
        return in_array($group_name, $this->listGroups($server));
    }

    /** should be protected (used publicly only in tests), like listProjects */
    public function listGroups(Git_RemoteServer_GerritServer $server) {
        $command = self::COMMAND . ' ls-groups';
        return explode(PHP_EOL, $this->ssh->execute($server, $command));
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     *
     * @return array of (groupname => uuid)
     */
    public function getAllGroups(Git_RemoteServer_GerritServer $server) {
        $command = self::COMMAND . ' ls-groups --verbose';
        $results = explode(PHP_EOL, $this->ssh->execute($server, $command));

        $group_info = array();
        foreach ($results as $group_line) {
            $group_entry = explode("\t", $group_line);
            $name        = $group_entry[self::INDEX_GROUPS_VERBOSE_NAME];

            $group_info[$name] = $group_entry[self::INDEX_GROUPS_VERBOSE_UUID];
        }

        return $group_info;
    }

    private function computeException(Git_Driver_Gerrit_RemoteSSHCommandFailure $e, $command) {
        return $this->isGerritFailure($e) ? $this->gerritDriverException($e, $command) : $e;

    }

    private function isGerritFailure($e) {
        return $e->getExitCode() === self::EXIT_CODE;
    }

    private function gerritDriverException($e, $command) {
        return new Git_Driver_Gerrit_Exception("Command: $command".PHP_EOL."Error: ".$e->getStdErr());
    }

    public function getGerritProjectName(GitRepository $repository) {
        $name_builder = new Git_RemoteServer_Gerrit_ProjectNameBuilder();

        return $name_builder->getGerritProjectName($repository);
    }

    protected function setAccount(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user) {
        $this->logger->debug("Set account ".$user->getSSHUserName());
        $query = self::COMMAND .' set-account '. $user->getSSHUserName();
        $this->ssh->execute($server, $query);
    }

    /**
     * Add a user to a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param PFUser $user
     * @param String $group_name
     */
    public function addUserToGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name) {
        $this->setAccount($server, $user);

        $username = $user->getWebUserName();

        $sql_query = "INSERT INTO account_group_members (account_id, group_id) SELECT A.account_id, G.group_id FROM account_external_ids A, account_groups G WHERE A.external_id='username:". $username ."' AND G.name='". $group_name ."'";
        $this->executeQuery($server, $sql_query);
    }

    /**
     * Remove a user from a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param PFUser $user
     * @param String $group_name
     */
    public function removeUserFromGroup(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $group_name) {
        $username = $user->getWebUserName();

        $sql_query = "DELETE FROM account_group_members WHERE account_id=(SELECT account_id FROM account_external_ids WHERE external_id='username:". $username ."') AND group_id=(SELECT group_id FROM account_groups WHERE name='". $group_name ."')";
        $this->executeQuery($server, $sql_query);
    }

    /**
     * Remove all members from a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param String $group_name
     */
    public function removeAllGroupMembers(Git_RemoteServer_GerritServer $server, $group_name) {
        $sql_query = "DELETE FROM account_group_members WHERE group_id=(SELECT group_id FROM account_groups WHERE name='". $group_name ."')";
        $this->executeQuery($server, $sql_query);
        $this->flushGerritCacheAccounts($server);
    }

    /**
     * Add a user group as member of another user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param String $group_name
     * @param String $included_group_name
     */
    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name) {
        $this->insertAccountGroupIncludes($server, $group_name, $included_group_name);
        $this->flushGerritCacheGroupsInclude($server);
    }

    private function insertAccountGroupIncludes(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name) {
        $sql_query = "INSERT INTO account_group_includes (group_id, include_id) SELECT G.group_id, I.group_id FROM account_groups G, account_groups I WHERE G.name='".$group_name."' AND I.name='".$included_group_name."'";
        $this->executeQuery($server, $sql_query);
    }

    /**
     * Remove all user groups that are member of a user group on a given gerrit server
     *
     * Can throw Git_Driver_Gerrit_RemoteSSHCommandFailure exception
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param String $group_name
     */
    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name) {
        $this->removeAccountGroupIncludes($server, $this->getGroupId($server, $group_name));
        $this->flushGerritCacheGroupsInclude($server);
    }

    private function removeAccountGroupIncludes(Git_RemoteServer_GerritServer $server, $gerrit_group_id) {
        $sql_query = "DELETE FROM account_group_includes I WHERE I.group_id=$gerrit_group_id";
        $this->executeQuery($server, $sql_query);
    }

    public function flushGerritCacheAccounts($server) {
        $this->flushGerritCaches($server, self::CACHE_ACCOUNTS);
    }

    private function flushGerritCacheGroupsInclude($server) {
        $this->flushGerritCaches($server, self::CACHE_GROUPS_INCLUDES);
    }

    private function flushGerritCaches($server, $cache=null) {
        $query = self::COMMAND .' flush-caches';
        if ($cache) {
            $query .= ' --cache '.$cache;
        }
        $this->ssh->execute($server, $query);
    }

    private function escapeSQLQuery($string) {
        $escaped_string = str_replace(' ', '\ ', $string);
        $escaped_string = str_replace("'", "\\'", $escaped_string);

        return $escaped_string;
    }

    private function executeQuery(Git_RemoteServer_GerritServer $server, $sql_query) {
        $query = self::GSQL_COMMAND .' '. $this->escapeSQLQuery('"'.$sql_query.'"');
        return $this->ssh->execute($server, $query);
    }


    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param Git_Driver_Gerrit_User $user
     * @param string $ssh_key
     * @throws Git_Driver_Gerrit_Exception
     *
     */
    public function addSSHKeyToAccount(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $ssh_key) {
        $escaped_ssh_key = escapeshellarg($ssh_key);
        $query = self::COMMAND .' set-account --add-ssh-key "'. $escaped_ssh_key .'" '. $user->getSSHUserName();
        $this->ssh->execute($server, $query);
    }

    /**
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param Git_Driver_Gerrit_User $user
     * @param string $ssh_key
     * @throws Git_Driver_Gerrit_Exception
     */
    public function removeSSHKeyFromAccount(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit_User $user, $ssh_key) {
        $escaped_ssh_key = escapeshellarg($ssh_key);
        $query = self::COMMAND .' set-account --delete-ssh-key "'. $escaped_ssh_key .'" '. $user->getSSHUserName();
        $this->ssh->execute($server, $query);
    }

    /**
     * Set the parent of a project
     * @param Git_RemoteServer_GerritServer $server
     * @param string $project_name
     * @param string $parent_project_name
     */
    public function setProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name, $parent_project_name) {
        $query = self::COMMAND .' set-project-parent '. $project_name .' --parent '. $parent_project_name;
        $this->ssh->execute($server, $query);
    }

    /**
     * Reset the parent of a project
     * @param Git_RemoteServer_GerritServer $server
     * @param string $project_name
     */
    public function resetProjectInheritance(Git_RemoteServer_GerritServer $server, $project_name) {
        $query = self::COMMAND .' set-project-parent '. $project_name .' --parent '. self::DEFAULT_PARENT_PROJECT;
        $this->ssh->execute($server, $query);

    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     * @return boolean
     */
    public function isDeletePluginEnabled(Git_RemoteServer_GerritServer $server) {
        $query = self::COMMAND . ' plugin ls';

        try {
            $plugins_list = $this->ssh->execute($server, $query);
        } catch(Exception $e) {
            return false;
        }

        if (! $this->isDeletePluginInstalled($plugins_list)) {
            return false;
        }

        $plugin     = preg_quote(self::DELETEPROJECT_PLUGIN_NAME);
        $is_enabled = preg_quote(self::GERRIT_PLUGIN_ENABLED_VALUE);

        return preg_match("/\s$plugin\s+[^\s]+\s+$is_enabled/", $plugins_list);
    }

    private function isDeletePluginInstalled($plugins_list) {
        return strstr($plugins_list, self::DELETEPROJECT_PLUGIN_NAME);
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     * @param string $gerrit_project_full_name E.g. bugs or bugs/repository1
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function deleteProject(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name) {
        $query = ' deleteproject delete ' . $gerrit_project_full_name . ' --yes-really-delete';
        try {
            $this->ssh->execute($server, $query);
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $exception) {
            $this->throwMeaningfullException($exception);
        }
    }

    private function throwMeaningfullException(Git_Driver_Gerrit_RemoteSSHCommandFailure $exception) {
        if ($this->isGerritFailure($exception)) {
            throw new ProjectDeletionException(
                $GLOBALS['Language']->getText('plugin_git', 'project_deletion_open_elements')
            );
        }

        throw $exception;
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     * @param string $gerrit_project_full_name
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function makeGerritProjectReadOnly(Git_RemoteServer_GerritServer $server, $gerrit_project_full_name) {
        $query = self::COMMAND . ' set-project --ps READ_ONLY ' . $gerrit_project_full_name;
        $this->ssh->execute($server, $query);
    }

    /**
     * @param Git_RemoteServer_GerritServer $server
     * @param PFUser $gerrit_users_to_suspend
     *
     * @throws Git_Driver_Gerrit_Exception
     */
    public function setUserAccountInactive(Git_RemoteServer_GerritServer $server, PFUser $user) {
        $query = self::COMMAND . ' set-account --inactive ' . $user->getUserName();
        try {
            $this->ssh->execute($server, $query);
            $this->logger->info($GLOBALS['Language']->getText('plugin_git', 'gerrit_user_suspension_successful', array($user->getId(), $user->getUserName(), $server)));
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $exception) {
            $this->logger->error($GLOBALS['Language']->getText('plugin_git', 'gerrit_user_suspension_error', array($user->getId(), $user->getUserName(), $server, $exception->getStdErr())));
        }
    }
}
