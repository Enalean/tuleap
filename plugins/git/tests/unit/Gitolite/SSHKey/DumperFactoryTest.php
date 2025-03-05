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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite\SSHKey;

use Git_Exec;
use Git_Gitolite_SSHKeyDumper;
use Git_Gitolite_SSHKeyMassDumper;
use Psr\Log\NullLogger;
use System_Command;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DumperFactoryTest extends TestCase
{
    public function testItBuildsGitoliteDumperIfTuleapDoesNotManageAuthorizedKeysFile(): void
    {
        $management_detector = $this->createMock(ManagementDetector::class);
        $management_detector->method('isAuthorizedKeysFileManagedByTuleap')->willReturn(false);
        $authorized_keys_file_creator = $this->createMock(AuthorizedKeysFileCreator::class);
        $system_command               = $this->createMock(System_Command::class);
        $git_exec                     = $this->createMock(Git_Exec::class);
        $user_manager                 = $this->createMock(UserManager::class);
        $logger                       = new NullLogger();

        $dumper_factory = new DumperFactory(
            $management_detector,
            $authorized_keys_file_creator,
            $system_command,
            $git_exec,
            '',
            $user_manager,
            $logger
        );

        self::assertInstanceOf(Git_Gitolite_SSHKeyDumper::class, $dumper_factory->buildDumper());
        self::assertInstanceOf(Git_Gitolite_SSHKeyMassDumper::class, $dumper_factory->buildMassDumper());
    }

    public function testItBuildsTuleapDumperIfTuleapManagesAuthorizedKeysFile(): void
    {
        $management_detector = $this->createMock(ManagementDetector::class);
        $management_detector->method('isAuthorizedKeysFileManagedByTuleap')->willReturn(true);
        $authorized_keys_file_creator = $this->createMock(AuthorizedKeysFileCreator::class);
        $system_command               = $this->createMock(System_Command::class);
        $git_exec                     = $this->createMock(Git_Exec::class);
        $user_manager                 = $this->createMock(UserManager::class);
        $logger                       = new NullLogger();

        $dumper_factory = new DumperFactory(
            $management_detector,
            $authorized_keys_file_creator,
            $system_command,
            $git_exec,
            '',
            $user_manager,
            $logger
        );

        self::assertInstanceOf(Gitolite3Dumper::class, $dumper_factory->buildDumper());
        self::assertInstanceOf(Gitolite3MassDumper::class, $dumper_factory->buildMassDumper());
    }
}
