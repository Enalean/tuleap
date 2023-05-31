<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\SSHAuthenticate;

use Tuleap\GitLFS\Authorization\User\Operation\UnknownUserOperationException;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionContent;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class SSHAuthenticateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SSHAuthenticate $auth;
    private \GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject $git_repository_factory;
    private \ProjectManager&\PHPUnit\Framework\MockObject\MockObject $project_manager;
    private \UserManager&\PHPUnit\Framework\MockObject\MockObject $user_manager;
    private \gitlfsPlugin&\PHPUnit\Framework\MockObject\MockObject $plugin;
    private \PHPUnit\Framework\MockObject\MockObject&SSHAuthenticateResponseBuilder $ssh_response;
    private \Tuleap\GitLFS\Authorization\User\Operation\UserOperationFactory&\PHPUnit\Framework\MockObject\MockObject $user_operation_factory;

    protected function setUp(): void
    {
        $this->project_manager        = $this->createMock(\ProjectManager::class);
        $this->user_manager           = $this->createMock(\UserManager::class);
        $this->git_repository_factory = $this->createMock(\GitRepositoryFactory::class);
        $this->plugin                 = $this->createMock(\gitlfsPlugin::class);
        $this->ssh_response           = $this->createMock(SSHAuthenticateResponseBuilder::class);
        $this->user_operation_factory = $this->createMock(\Tuleap\GitLFS\Authorization\User\Operation\UserOperationFactory::class);

        $this->auth = new SSHAuthenticate(
            $this->project_manager,
            $this->user_manager,
            $this->git_repository_factory,
            $this->ssh_response,
            $this->user_operation_factory,
            $this->plugin
        );
    }

    public function testItFailsWhenThereAreNoArguments(): void
    {
        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate']);
    }

    public function testSecondArgumentIsNotAValidOperation(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')->willThrowException(new UnknownUserOperationException('test'));

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'faa.git', 'foo']);
    }

    public function test1stArgWithInvalidProjectNameMustFail(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($this->createMock(UserOperation::class));
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->willReturn(null);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function test1stArgWithNonActiveProjectMustFail(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($this->createMock(UserOperation::class));

        $project = ProjectTestBuilder::aProject()->withStatusSuspended()->build();
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->with('foo')->willReturn($project);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function test1stArgWithInvalidRepositoryMustFail(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($this->createMock(UserOperation::class));

        $project = ProjectTestBuilder::aProject()->withId(122)->build();
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->willReturn($project);

        $this->git_repository_factory->method('getRepositoryByPath')->with(122, 'foo/faa.git')->willReturn(null);

        $this->plugin->method('isAllowed')->with(122)->willReturn(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserNotFoundMustHaveAFailure(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($this->createMock(UserOperation::class));

        $project = ProjectTestBuilder::aProject()->withId(122)->build();
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->willReturn($project);

        $repository = $this->createMock(\GitRepository::class);
        $repository->expects(self::never())->method('userCanRead');
        $this->git_repository_factory->method('getRepositoryByPath')->with(122, 'foo/faa.git')->willReturn($repository);

        $this->user_manager->method('getUserByUserName')->with('mary')->willReturn(null);

        $this->plugin->method('isAllowed')->with(122)->willReturn(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserNotActiveMustHaveAFailure(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($this->createMock(UserOperation::class));

        $project = ProjectTestBuilder::aProject()->withId(122)->build();
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->willReturn($project);

        $repository = $this->createMock(\GitRepository::class);
        $repository->expects(self::never())->method('userCanRead');
        $this->git_repository_factory->method('getRepositoryByPath')->with(122, 'foo/faa.git')->willReturn($repository);

        $user = UserTestBuilder::aUser()->withStatus(\PFUser::STATUS_SUSPENDED)->build();
        $this->user_manager->method('getUserByUserName')->with('mary')->willReturn($user);

        $this->plugin->method('isAllowed')->with(122)->willReturn(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserWithoutReadAccessToRepoMustHaveAFailure(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($this->createMock(UserOperation::class));

        $project = ProjectTestBuilder::aProject()->withId(122)->build();
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->willReturn($project);

        $user = UserTestBuilder::anActiveUser()->build();
        $this->user_manager->method('getUserByUserName')->with('mary')->willReturn($user);

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('userCanRead')->with($user)->willReturn(false);
        $this->git_repository_factory->method('getRepositoryByPath')->with(122, 'foo/faa.git')->willReturn($repository);

        $this->plugin->method('isAllowed')->with(122)->willReturn(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testNoAccessWhenPluginIsNotAvailable(): void
    {
        $auth = new SSHAuthenticate(
            $this->project_manager,
            $this->user_manager,
            $this->git_repository_factory,
            $this->ssh_response,
            $this->user_operation_factory,
            null
        );

        $this->expectException(InvalidCommandException::class);

        $auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testNoAccessWhenPluginIsNotGrantedForProject(): void
    {
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($this->createMock(UserOperation::class));

        $project = ProjectTestBuilder::aProject()->withId(122)->build();
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->willReturn($project);

        $user = UserTestBuilder::anActiveUser()->build();
        $this->user_manager->method('getUserByUserName')->with('mary')->willReturn($user);

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('userCanRead')->with($user)->willReturn(true);
        $this->git_repository_factory->method('getRepositoryByPath')->with(122, 'foo/faa.git')->willReturn($repository);

        $this->plugin->method('isAllowed')->with(122)->willReturn(false);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    /**
     * @dataProvider dataProviderRepositoryPath
     */
    public function testItReturnsBatchResponseActionContentWhenEverythingIsOk(string $repository_path): void
    {
        $user_operation = $this->createMock(UserOperation::class);
        $this->user_operation_factory->method('getUserOperationFromName')
            ->willReturn($user_operation);

        $project = ProjectTestBuilder::aProject()->withId(122)->build();
        $this->project_manager->method('getProjectByCaseInsensitiveUnixName')->with('foo')->willReturn($project);

        $user = UserTestBuilder::anActiveUser()->build();
        $this->user_manager->method('getUserByUserName')->with('mary')->willReturn($user);

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('userCanRead')->with($user)->willReturn(true);
        $this->git_repository_factory->method('getRepositoryByPath')->with(122, 'foo/faa.git')->willReturn($repository);

        $this->plugin->method('isAllowed')->with(122)->willReturn(true);

        $this->ssh_response->method('getResponse')->with(
            $repository,
            $user,
            $user_operation,
            self::anything(),
        )->willReturn($this->createMock(BatchResponseActionContent::class));

        $response = $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', $repository_path, 'download']);
        self::assertInstanceOf(BatchResponseActionContent::class, $response);
    }

    /**
     * @return \string[][]
     * @psalm-return array<string, array{0: string}>
     */
    public static function dataProviderRepositoryPath(): array
    {
        return [
            'Git LFS before 3.0.0' => ['foo/faa.git'],
            'Git LFS starting 3.0.0' => ['/foo/faa.git'],
        ];
    }
}
