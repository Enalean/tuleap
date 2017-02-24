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

namespace Tuleap\Git\Gitolite\SSHKey;

use TuleapTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

class DumperFactoryTest extends TuleapTestCase
{
    public function itBuildsGitoliteDumperIfTuleapDoesNotManageAuthorizedKeysFile()
    {
        $management_detector          = mock('Tuleap\Git\Gitolite\SSHKey\ManagementDetector');
        stub($management_detector)->isAuthorizedKeysFileManagedByTuleap()->returns(false);
        $authorized_keys_file_creator = mock('Tuleap\Git\Gitolite\SSHKey\AuthorizedKeysFileCreator');
        $system_command               = mock('System_Command');
        $git_exec                     = mock('Git_Exec');
        $user_manager                 = mock('UserManager');

        $dumper_factory = new DumperFactory(
            $management_detector,
            $authorized_keys_file_creator,
            $system_command,
            $git_exec,
            '',
            $user_manager
        );

        $this->assertIsA($dumper_factory->buildDumper(), 'Git_Gitolite_SSHKeyDumper');
        $this->assertIsA($dumper_factory->buildMassDumper(), 'Git_Gitolite_SSHKeyMassDumper');
    }

    public function itBuildsTuleapDumperIfTuleapManagesAuthorizedKeysFile()
    {
        $management_detector          = mock('Tuleap\Git\Gitolite\SSHKey\ManagementDetector');
        stub($management_detector)->isAuthorizedKeysFileManagedByTuleap()->returns(true);
        $authorized_keys_file_creator = mock('Tuleap\Git\Gitolite\SSHKey\AuthorizedKeysFileCreator');
        $system_command               = mock('System_Command');
        $git_exec                     = mock('Git_Exec');
        $user_manager                 = mock('UserManager');

        $dumper_factory = new DumperFactory(
            $management_detector,
            $authorized_keys_file_creator,
            $system_command,
            $git_exec,
            '',
            $user_manager
        );

        $this->assertIsA($dumper_factory->buildDumper(), 'Tuleap\Git\Gitolite\SSHKey\Gitolite3Dumper');
        $this->assertIsA($dumper_factory->buildMassDumper(), 'Tuleap\Git\Gitolite\SSHKey\Gitolite3MassDumper');
    }
}
