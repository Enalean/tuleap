<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite\SSHKey\SystemEvent;

use System_Command;
use System_Command_CommandException;
use Tuleap\Git\GlobalParameterDao;

class MigrateToTuleapSSHKeyManagement extends \SystemEvent
{
    const NAME              = 'MIGRATE_TO_TULEAP_SSH_KEY_MANAGEMENT';
    const GITOLITE3_RC_PATH = '/var/lib/gitolite/.gitolite.rc';

    /**
     * @var GlobalParameterDao
     */
    private $global_parameter_dao;
    /**
     * @var System_Command
     */
    private $system_command;

    public function injectDependencies(
        GlobalParameterDao $global_parameter_dao,
        System_Command $system_command
    ) {
        $this->global_parameter_dao = $global_parameter_dao;
        $this->system_command       = $system_command;
    }

    public function verbalizeParameters($with_link)
    {
        return '';
    }

    public function process()
    {
        try {
            $this->system_command->exec('sed -i "s/\'ssh-authkeys\',/#\'ssh-authkeys\',/" ' . escapeshellarg(self::GITOLITE3_RC_PATH));
        } catch (System_Command_CommandException $ex) {
            $this->error($ex->getMessage());
            return;
        }

        $has_been_enabled = $this->global_parameter_dao->enableAuthorizedKeysFileManagementByTuleap();
        if ($has_been_enabled === false) {
            $this->error('The database has not been able to save the new manager of the authorized keys file');
            return;
        }

        $this->done();
    }
}
