<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\PreReceive;

use GitRepository;
use GitRepositoryFactory;
use Symfony\Component\Process\Process;
use Tuleap\PullRequest\GitExec;
use Tuleap\TemporaryTestDirectory;

final class PreReceiveAnalyzeActionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private \PHPUnit\Framework\MockObject\MockObject|GitRepositoryFactory $git_repository_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
    }

    public function testRepoDoesNotExist(): void
    {
        $action = new PreReceiveAnalyzeAction($this->git_repository_factory);

        $this->git_repository_factory->method('getRepositoryById')->with(666)->willReturn(null);

        $this->expectException(PreReceiveRepositoryNotFoundException::class);
        $action->preReceiveAnalyse('666', 'aaaaaaa');
    }

    public function testReferenceDoesNotExist(): void
    {
        $action = new PreReceiveAnalyzeAction($this->git_repository_factory);

        $remote_repo = $this->getTmpDir() . '/some_git_repo.git';
        mkdir($remote_repo);
        (new Process([GitExec::getGitCommand(), '-C', $remote_repo, 'init', '--bare']))->mustRun();

        $working_copy = $this->getTmpDir() . '/some_git_repo_wc';
        (new Process([GitExec::getGitCommand(), 'clone', $remote_repo, $working_copy]))->mustRun();
        file_put_contents($working_copy . '/Readme.mkd', 'foo');
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'config', 'user.email', 'test@test.fr']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'config', 'user.name', 'test']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'add', $working_copy . '/Readme.mkd']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'commit', '-m', 'Add readme']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'push']))->mustRun();

        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getFullPath')->willReturn($remote_repo);
        $this->git_repository_factory->method('getRepositoryById')->with(42)->willReturn($git_repository);

        $this->expectException(PreReceiveCannotRetrieveReferenceException::class);
        $action->preReceiveAnalyse('42', '469eaa9');
    }

    public function testNormalBehavior(): void
    {
        $action = new PreReceiveAnalyzeAction($this->git_repository_factory);

        $remote_repo = $this->getTmpDir() . '/some_git_repo.git';
        mkdir($remote_repo);
        (new Process([GitExec::getGitCommand(), '-C', $remote_repo, 'init', '--bare']))->mustRun();

        $working_copy = $this->getTmpDir() . '/some_git_repo_wc';
        (new Process([GitExec::getGitCommand(), 'clone', $remote_repo, $working_copy]))->mustRun();
        file_put_contents($working_copy . '/Readme.mkd', 'foo');
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'config', 'user.email', 'test@test.fr']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'config', 'user.name', 'test']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'add', $working_copy . '/Readme.mkd']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'commit', '-m', 'Add readme']))->mustRun();
        (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'push']))->mustRun();

        $sha1           = (new Process([GitExec::getGitCommand(), '-C', $working_copy, 'rev-parse', 'HEAD']))->mustRun()->getOutput();
        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getFullPath')->willReturn($remote_repo);
        $this->git_repository_factory->method('getRepositoryById')->with(42)->willReturn($git_repository);

        $returnCode = $action->preReceiveAnalyse('42', trim($sha1));

        $this->assertEquals(0, $returnCode);
    }
}
