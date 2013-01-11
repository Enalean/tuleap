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

    public function createProject(Git_RemoteServer_GerritServer $server, GitRepository $repository) {
        $gerrit_project = $this->getGerritProjectName($repository);
        $command = implode(' ',array(self::COMMAND, 'create-project', $gerrit_project));
        try {
            $this->ssh->execute($server, $command);
            $project_name = $this->getGerritProjectName($repository);
            $this->logger->info("Gerrit: Project $project_name successfully initialized");
            return $project_name;
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            throw $this->computeException($e, $command);
        }

    }

    public function createGroup(Git_RemoteServer_GerritServer $server, GitRepository $repository, $group_name, $user_list){
        $gerrit_group = $this->getGerritProjectName($repository)."-$group_name";
        $base_command = array(self::COMMAND, "create-group", $gerrit_group);
        $members      = $this->compileMemberCommands($user_list);
        $command_line = implode(' ',array_merge($base_command, $members));
        try {
            $this->ssh->execute($server, $command_line);
        } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            throw $this->computeException($e, $command_line);
        }

        $this->logger->info("Gerrit: Group $gerrit_group successfully created");
    }

    public function getGroupUUID(Git_RemoteServer_GerritServer $server, $group_full_name) {
        $command = self::GSQL_COMMAND .' "SELECT\ group_uuid\ FROM\ account_groups\ WHERE\ name=\\\''. $group_full_name .'\\\'"';
        $command_result = $this->ssh->execute($server, $command);
        $json_result = json_decode(array_shift(explode("\n", $command_result)));
        if (isset($json_result->columns->group_uuid)) {
            return $json_result->columns->group_uuid;
        }
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
        $host    = Config::get('sys_default_domain');
        $project = $repository->getProject()->getUnixName();
        $repo    = $repository->getFullName();
        return "$host-$project/$repo";
    }

    private function compileMemberCommands($user_list) {
        $members = array();
        foreach ($user_list as $user) {
            $user = $this->escapeUserIdentifierAsWeNeedToGiveTheParameterToGsqlBehindSSH($user);
            $members[] = "--member $user";
        }
        return $members;
    }

    private function escapeUserIdentifierAsWeNeedToGiveTheParameterToGsqlBehindSSH($user_identifier) {
        return escapeshellarg(escapeshellarg($user_identifier));
    }
}
?>
