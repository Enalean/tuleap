<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'Gerrit/RemoteSSHCommand.class.php';
require_once 'Gerrit/Exception.class.php';
require_once GIT_BASE_DIR .'/Git/RemoteServer/GerritServer.class.php';
require_once 'common/backend/BackendLogger.class.php';


/**
 * I know how to speak to a Gerrit remote server
 */
class Git_Driver_Gerrit {

    const COMMAND      = 'gerrit';
    const GSQL_COMMAND = 'gerrit gsql --format json -c';
    const EXIT_CODE    = 1;

    /**
     * @var Git_Driver_Gerrit_RemoteSSHCommand
     */
    private $ssh;

    /** @var Logger */
    private $logger;

    public function __construct(Git_Driver_Gerrit_RemoteSSHCommand $ssh, Logger $logger) {
        $this->ssh = $ssh;
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
     * @param GitRepository $repository
     * @param String $admin_group_name
     * @return String Gerrit parent project name
     */
    public function createParentProject(Git_RemoteServer_GerritServer $server, GitRepository $repository, $admin_group_name) {
        $project_parent_name = $repository->getProject()->getUnixName();
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
     * @throws Git_Driver_Gerrit_RemoteSSHCommandFailure
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
     * @param type $project_name
     * @return true if the gerrit project exists, else return false
     */
    public function doesTheParentProjectExist(Git_RemoteServer_GerritServer $server, $project_name) {
        return in_array($project_name, $this->listParentProjects($server));
    }

    /**
     *
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
     * @param array $user_name_list
     */
    public function createGroup(Git_RemoteServer_GerritServer $server, $group_name, array $user_name_list){
        if ($this->doesTheGroupExist($server, $group_name)) {
            $this->logger->info("Gerrit: Group $group_name already exists on Gerrit");
            return;
        }

        $base_command = array(self::COMMAND, "create-group", $group_name);
        $member_args  = $this->compileMemberCommands($user_name_list);
        $command_line = implode(' ', array_merge($base_command, $member_args));
        try {
            $this->ssh->execute($server, $command_line);
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            throw $this->computeException($e, $command_line);
        }

        $this->logger->info("Gerrit: Group $group_name successfully created");
    }

    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name) {
        $command = self::GSQL_COMMAND .' "SELECT\ group_uuid\ FROM\ account_groups\ WHERE\ name=\\\''. $group_full_name .'\\\'"';
        $command_result = $this->ssh->execute($server, $command);
        $json_result = json_decode(array_shift(explode("\n", $command_result)));
        if (isset($json_result->columns->group_uuid)) {
            return $json_result->columns->group_uuid;
        }
    }

    public function doesTheGroupExist(Git_RemoteServer_GerritServer $server, $group_name) {
        return in_array($group_name, $this->listGroups($server));
    }

    public function listGroups(Git_RemoteServer_GerritServer $server) {
        $command = self::COMMAND . ' ls-groups';
        return explode(PHP_EOL, $this->ssh->execute($server, $command));
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
        $project = $repository->getProject()->getUnixName();
        $repo    = $repository->getFullName();
        return "$project/$repo";
    }

    private function compileMemberCommands($user_name_list) {
        $member_args = array();
        foreach ($user_name_list as $user_name) {
            $user_name = $this->escapeUserIdentifierAsWeNeedToGiveTheParameterToGsqlBehindSSH($user_name);
            $member_args[] = "--member $user_name";
        }
        return $member_args;
    }

    private function escapeUserIdentifierAsWeNeedToGiveTheParameterToGsqlBehindSSH($user_identifier) {
        return escapeshellarg(escapeshellarg($user_identifier));
    }

    public function setAccount(Git_RemoteServer_GerritServer $server, PFUser $user) {
        $query = self::COMMAND .' set-account '. $user->getLdapId();
        $this->ssh->execute($server, $query);
    }

    public function addUserToGroup(Git_RemoteServer_GerritServer $server, PFUser $user, $group_name) {
        $this->setAccount($server, $user);

        $username = $user->getLdapId();

        $sql_query = "INSERT INTO account_group_members (account_id, group_id) SELECT A.account_id, G.group_id FROM account_external_ids A, account_groups G WHERE A.external_id='username:". $username ."' AND G.name='". $group_name ."'";

        $query = self::GSQL_COMMAND .' '. $this->escapeSQLQuery('"'.$sql_query.'"');

        $this->ssh->execute($server, $query);
        $this->flushGerritCaches($server);
    }

    public function removeUserFromGroup(Git_RemoteServer_GerritServer $server, PFUser $user, $group_name) {
        $username = $user->getLdapId();

        $sql_query = "DELETE FROM account_group_members WHERE account_id=(SELECT account_id FROM account_external_ids WHERE external_id='username:". $username ."') AND group_id=(SELECT group_id FROM account_groups WHERE name='". $group_name ."')";

        $query = self::GSQL_COMMAND .' '. $this->escapeSQLQuery('"'.$sql_query.'"');

        $this->ssh->execute($server, $query);
        $this->flushGerritCaches($server);
    }

    public function addIncludedGroup(Git_RemoteServer_GerritServer $server, $group_name, $included_group_name) {
    }

    public function removeAllIncludedGroups(Git_RemoteServer_GerritServer $server, $group_name) {
    }

    private function flushGerritCaches($server) {
        $query = self::COMMAND .' flush-caches';
        $this->ssh->execute($server, $query);
    }

    private function escapeSQLQuery($query) {
        $escaped_query = str_replace(' ', '\ ', $query);
        $escaped_query = str_replace("'", "\\'", $escaped_query);

        return $escaped_query;
    }
}
?>
