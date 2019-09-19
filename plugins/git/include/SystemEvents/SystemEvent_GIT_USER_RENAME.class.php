<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

use Tuleap\Git\Gitolite\SSHKey\Dumper;
use Tuleap\Git\Gitolite\SSHKey\InvalidKeysCollector;

class SystemEvent_GIT_USER_RENAME extends SystemEvent
{

    public const NAME = "GIT_USER_RENAME";

    /** @var Git_Gitolite_SSHKeyDumper*/
    private $ssh_key_dumper;

    /** @var UserManager */
    private $user_manager;

    public function process()
    {
        $old_user_name = $this->getParameter(0);
        $new_user_id   = $this->getParameter(1);

        try {
            $new_user = $this->user_manager->getUserById($new_user_id);

            $this->ssh_key_dumper->removeAllExistingKeysForUserName($old_user_name);
            $this->ssh_key_dumper->dumpSSHKeys($new_user, new InvalidKeysCollector());
        } catch (Exception $e) {
            $this->error($e->getMessage() . $e->getTraceAsString());
        }

        $this->done();
        return true;
    }

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }

    public function injectDependencies(Dumper $ssh_key_dumper, UserManager $user_manager)
    {
        $this->ssh_key_dumper = $ssh_key_dumper;
        $this->user_manager   = $user_manager;
    }
}
