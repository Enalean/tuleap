<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey;

use Git_Exec;
use Git_Gitolite_SSHKeyDumper;
use Git_Gitolite_SSHKeyMassDumper;
use Psr\Log\LoggerInterface;
use System_Command;
use UserManager;

class DumperFactory
{
    /**
     * @var ManagementDetector
     */
    private $management_detector;
    /**
     * @var AuthorizedKeysFileCreator
     */
    private $authorized_keys_file_creator;
    /**
     * @var System_Command
     */
    private $system_command;
    /**
     * @var Git_Exec
     */
    private $git_exec;
    /**
     * @var string
     */
    private $admin_path;
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ManagementDetector $management_detector,
        AuthorizedKeysFileCreator $authorized_keys_file_creator,
        System_Command $system_command,
        Git_Exec $git_exec,
        $admin_path,
        UserManager $user_manager,
        LoggerInterface $logger
    ) {
        $this->management_detector          = $management_detector;
        $this->authorized_keys_file_creator = $authorized_keys_file_creator;
        $this->system_command               = $system_command;
        $this->git_exec                     = $git_exec;
        $this->admin_path                   = $admin_path;
        $this->user_manager                 = $user_manager;
        $this->logger                       = $logger;
    }

    /**
     * @return Dumper
     */
    public function buildDumper()
    {
        if ($this->management_detector->isAuthorizedKeysFileManagedByTuleap()) {
            return new Gitolite3Dumper($this->authorized_keys_file_creator, $this->system_command, $this->logger);
        }
        return new Git_Gitolite_SSHKeyDumper($this->admin_path, $this->git_exec);
    }

    /**
     * @return MassDumper
     */
    public function buildMassDumper()
    {
        if ($this->management_detector->isAuthorizedKeysFileManagedByTuleap()) {
            return new Gitolite3MassDumper($this->buildDumper());
        }
        return new Git_Gitolite_SSHKeyMassDumper($this->buildDumper(), $this->user_manager);
    }
}
