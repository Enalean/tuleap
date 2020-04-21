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

use Git_Gitolite_SSHKeyDumper;
use Git_Gitolite_SSHKeyMassDumper;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../bootstrap.php';

class DumperFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsGitoliteDumperIfTuleapDoesNotManageAuthorizedKeysFile()
    {
        $management_detector          = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\ManagementDetector::class);
        $management_detector->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturnFalse();
        $authorized_keys_file_creator = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\AuthorizedKeysFileCreator::class);
        $system_command               = \Mockery::spy(\System_Command::class);
        $git_exec                     = \Mockery::spy(\Git_Exec::class);
        $user_manager                 = \Mockery::spy(\UserManager::class);
        $logger                       = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        $dumper_factory = new DumperFactory(
            $management_detector,
            $authorized_keys_file_creator,
            $system_command,
            $git_exec,
            '',
            $user_manager,
            $logger
        );

        $this->assertInstanceOf(Git_Gitolite_SSHKeyDumper::class, $dumper_factory->buildDumper());
        $this->assertInstanceOf(Git_Gitolite_SSHKeyMassDumper::class, $dumper_factory->buildMassDumper());
    }

    public function testItBuildsTuleapDumperIfTuleapManagesAuthorizedKeysFile()
    {
        $management_detector          = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\ManagementDetector::class);
        $management_detector->shouldReceive('isAuthorizedKeysFileManagedByTuleap')->andReturnTrue();
        $authorized_keys_file_creator = \Mockery::spy(\Tuleap\Git\Gitolite\SSHKey\AuthorizedKeysFileCreator::class);
        $system_command               = \Mockery::spy(\System_Command::class);
        $git_exec                     = \Mockery::spy(\Git_Exec::class);
        $user_manager                 = \Mockery::spy(\UserManager::class);
        $logger                       = \Mockery::mock(\Psr\Log\LoggerInterface::class);

        $dumper_factory = new DumperFactory(
            $management_detector,
            $authorized_keys_file_creator,
            $system_command,
            $git_exec,
            '',
            $user_manager,
            $logger
        );

        $this->assertInstanceOf(Gitolite3Dumper::class, $dumper_factory->buildDumper());
        $this->assertInstanceOf(Gitolite3MassDumper::class, $dumper_factory->buildMassDumper());
    }
}
