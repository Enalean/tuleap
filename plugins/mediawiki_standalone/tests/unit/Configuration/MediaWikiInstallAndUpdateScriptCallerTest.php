<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Configuration;

use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use Tuleap\MediawikiStandalone\Stub\MediaWikiManagementCommandFactoryStub;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MediaWikiInstallAndUpdateScriptCallerTest extends TestCase
{
    public function testExecutesInstallAndUpdateCommands(): void
    {
        $install_command            = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());
        $update_farm_config_command = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());
        $update_farm_command        = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());
        $update_instance_command_1  = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());
        $update_instance_command_2  = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());
        $update_instance_command_3  = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());

        $handler = $this->getHandler(
            $install_command,
            $update_farm_config_command,
            $update_farm_command,
            [
                $update_instance_command_1,
                $update_instance_command_2,
                $update_instance_command_3,
            ]
        );

        $handler->runInstallAndUpdate();

        self::assertTrue($install_command->has_been_waited_on);
        self::assertTrue($update_farm_config_command->has_been_waited_on);
        self::assertTrue($update_farm_command->has_been_waited_on);
        self::assertTrue($update_instance_command_1->has_been_waited_on);
        self::assertTrue($update_instance_command_2->has_been_waited_on);
        self::assertTrue($update_instance_command_3->has_been_waited_on);
    }

    public function testFailsWhenInstallCommandDoesNotSucceed(): void
    {
        $handler = $this->getHandler(
            new MediaWikiManagementCommandAlwaysFail(),
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandDoNothing(),
            [
                new MediaWikiManagementCommandDoNothing(),
            ]
        );

        $this->expectException(MediaWikiInstallAndUpdateHandlerException::class);
        $handler->runInstallAndUpdate();
    }

    public function testFailsWhenUpdateFarmConfigUpdateCommandDoesNotSucceed(): void
    {
        $handler = $this->getHandler(
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandAlwaysFail(),
            new MediaWikiManagementCommandDoNothing(),
            [
                new MediaWikiManagementCommandDoNothing(),
            ]
        );

        $this->expectException(MediaWikiInstallAndUpdateHandlerException::class);
        $handler->runInstallAndUpdate();
    }

    public function testFailsWhenUpdateFarmCommandDoesNotSucceed(): void
    {
        $handler = $this->getHandler(
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandAlwaysFail(),
            [
                new MediaWikiManagementCommandDoNothing(),
            ]
        );

        $this->expectException(MediaWikiInstallAndUpdateHandlerException::class);
        $handler->runInstallAndUpdate();
    }

    public function testFailsWhenAnUpdateInstanceCommandDoesNotSucceed(): void
    {
        $update_instance_command_1 = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());
        $update_instance_command_2 = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandAlwaysFail());
        $update_instance_command_3 = new MediaWikiManagementCommandObserver(new MediaWikiManagementCommandDoNothing());

        $handler = $this->getHandler(
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandDoNothing(),
            new MediaWikiManagementCommandDoNothing(),
            [
                $update_instance_command_1,
                $update_instance_command_2,
                $update_instance_command_3,
            ]
        );

        $this->expectException(MediaWikiInstallAndUpdateHandlerException::class);
        try {
            $handler->runInstallAndUpdate();
        } finally {
            self::assertTrue($update_instance_command_1->has_been_waited_on);
            self::assertTrue($update_instance_command_2->has_been_waited_on);
            self::assertTrue($update_instance_command_3->has_been_waited_on);
        }
    }

    /**
     * @param MediaWikiManagementCommand[] $update_instance_commands
     */
    private function getHandler(
        MediaWikiManagementCommand $install_command,
        MediaWikiManagementCommand $update_farm_config_file,
        MediaWikiManagementCommand $update_farm_command,
        array $update_instance_commands,
    ): MediaWikiInstallAndUpdateScriptCaller {
        $command_factory = new MediaWikiManagementCommandFactoryStub($install_command, $update_farm_config_file, $update_farm_command, $update_instance_commands);
        $dao             = $this->createStub(ProjectMediaWikiServiceDAO::class);
        $dao->method('searchAllProjectsWithMediaWikiStandaloneServiceReady')->willReturn(
            array_fill(0, count($update_instance_commands), ['project_name' => 'some_project_name'])
        );
        return new MediaWikiInstallAndUpdateScriptCaller(
            $command_factory,
            new MainpageDeployer(vfsStream::setup()->url()),
            new LocalSettingsInstantiator(
                new LocalSettingsRepresentationForTestBuilder(),
                new LocalSettingsPersistStub(),
                new DBTransactionExecutorPassthrough()
            ),
            $dao,
            new NullLogger()
        );
    }
}
