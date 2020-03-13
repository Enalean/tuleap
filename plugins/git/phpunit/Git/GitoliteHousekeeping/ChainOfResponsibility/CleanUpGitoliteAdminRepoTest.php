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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Tuleap\TemporaryTestDirectory;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepoTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    private $fixtures;
    /**
     * @var Git_GitoliteHousekeeping_ChainOfResponsibility_CleanUpGitoliteAdminRepo
     */
    private $command;
    /**
     * @var Git_GitoliteHousekeeping_GitoliteHousekeepingResponse|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $response;
    /**
     * @var string
     */
    private $remote_admin_repository;
    /**
     * @var string
     */
    private $expected_file_in_old_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = \Mockery::spy(\Git_GitoliteHousekeeping_GitoliteHousekeepingResponse::class);
        $this->fixtures = $this->getTmpDir();
        copy(__DIR__ . '/_fixtures/gitolite_admin.tgz', $this->fixtures . '/gitolite_admin.tgz');

        $this->remote_admin_repository = 'gitolite_admin';

        $tar_command = new Process(['tar', '-xzf', "$this->fixtures/gitolite_admin.tgz", '--directory', $this->fixtures]);
        $tar_command->mustRun();

        $clone_command = new Process(['git', 'clone', 'gitolite_admin', 'admin'], $this->fixtures);
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

    public function testItAbortsIfThereIsAlreadyABackupDir(): void
    {
        $next = \Mockery::spy(\Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        (new Process(['cp', '-r', 'admin', 'admin.old'], $this->fixtures))->mustRun();
        $this->command->setNextCommand($next);

        $this->response->shouldReceive('error')->with("The gitolite backup dir $this->fixtures/admin.old already exists. Please remove it.")->once();
        $this->response->shouldReceive('abort')->once();
        $next->shouldReceive('execute')->never();

        $this->command->execute();
    }

    public function testItMovesTheAdminDirInABackupDir(): void
    {
        $this->command->execute();

        $this->assertTrue(is_file($this->fixtures . '/admin.old/' . $this->expected_file_in_old_dir));
    }

    public function testItClonesAFreshRepository(): void
    {
        $this->command->execute();

        $this->assertTrue(is_dir($this->fixtures . '/admin/'));
        $this->assertFalse(is_dir($this->fixtures . '/admin/' . $this->expected_file_in_old_dir));
    }

    public function testItDisplaysMeaningfulFeedbackToTheUser(): void
    {
        $this->response->shouldReceive('info')->with("Moving admin to $this->fixtures/admin.old and cloning $this->remote_admin_repository")->once();

        $this->command->execute();
    }

    public function testItExecutesTheNextCommand(): void
    {
        $next = \Mockery::spy(\Git_GitoliteHousekeeping_ChainOfResponsibility_Command::class);
        $next->shouldReceive('execute')->once();

        $this->command->setNextCommand($next);

        $this->command->execute();
    }
}
