<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Git\GitoliteHousekeeping\ChainOfResponsibility;

use Git_Exec;
use Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo;
use Git_GitoliteHousekeeping_ChainOfResponsibility_Command;
use Git_GitoliteHousekeeping_GitoliteHousekeepingResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Process\Process;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CleanUpGitoliteAdminRepoTest extends TestCase
{
    use TemporaryTestDirectory;

    private string $fixtures;
    private Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo $command;
    private Git_GitoliteHousekeeping_GitoliteHousekeepingResponse&MockObject $response;
    private string $remote_admin_repository;
    private string $expected_file_in_old_dir;

    protected function setUp(): void
    {
        $this->response = $this->createMock(Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->fixtures = $this->getTmpDir();
        copy(__DIR__ . '/_fixtures/gitolite_admin.tgz', $this->fixtures . '/gitolite_admin.tgz');

        $this->remote_admin_repository = 'gitolite_admin';

        $tar_command = new Process(['tar', '-xzf', "$this->fixtures/gitolite_admin.tgz", '--directory', $this->fixtures]);
        $tar_command->mustRun();

        (new Process([Git_Exec::getGitCommand(), 'config', '--global', 'safe.directory', '*']))->mustRun();

        $clone_command = new Process([Git_Exec::getGitCommand(), 'clone', 'gitolite_admin', 'admin'], $this->fixtures);
        $clone_command->mustRun();

        $this->expected_file_in_old_dir = bin2hex(random_bytes(16));
        touch($this->fixtures . '/admin/' . $this->expected_file_in_old_dir);

        $this->command = new Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo(
            $this->response,
            $this->fixtures,
            $this->remote_admin_repository
        );
        $this->command->clearExecuteAs();
    }

    protected function tearDown(): void
    {
        (new Process([Git_Exec::getGitCommand(), 'config', '--global', '--unset', 'safe.directory']))->mustRun();
    }

    public function testItAbortsIfThereIsAlreadyABackupDir(): void
    {
        $next = $this->createMock(Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        (new Process(['cp', '-r', 'admin', 'admin.old'], $this->fixtures))->mustRun();
        $this->command->setNextCommand($next);

        $this->response->expects($this->once())->method('error')->with("The gitolite backup dir $this->fixtures/admin.old already exists. Please remove it.");
        $this->response->expects($this->once())->method('abort');
        $next->expects($this->never())->method('execute');

        $this->command->execute();
    }

    public function testItMovesTheAdminDirInABackupDir(): void
    {
        $this->response->method('info');
        $this->command->execute();

        self::assertTrue(is_file($this->fixtures . '/admin.old/' . $this->expected_file_in_old_dir));
    }

    public function testItClonesAFreshRepository(): void
    {
        $this->response->method('info');
        $this->command->execute();

        self::assertTrue(is_dir($this->fixtures . '/admin/'));
        self::assertFalse(is_dir($this->fixtures . '/admin/' . $this->expected_file_in_old_dir));
    }

    public function testItDisplaysMeaningfulFeedbackToTheUser(): void
    {
        $this->response->expects($this->once())->method('info')->with("Moving admin to $this->fixtures/admin.old and cloning $this->remote_admin_repository");

        $this->command->execute();
    }

    public function testItExecutesTheNextCommand(): void
    {
        $next = $this->createMock(Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        $next->expects($this->once())->method('execute');

        $this->command->setNextCommand($next);

        $this->response->method('info');
        $this->command->execute();
    }
}
