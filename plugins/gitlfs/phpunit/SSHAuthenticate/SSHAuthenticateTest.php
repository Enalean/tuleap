<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\SSHAuthenticate;

require_once __DIR__ . '/../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\Authorization\User\Operation\UnknownUserOperationException;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperationFactory;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActionContent;

class SSHAuthenticateTest extends TestCase
{

    use MockeryPHPUnitIntegration;

    private $auth;
    private $git_repository_factory;
    private $project_manager;
    private $user_manager;
    private $plugin;
    private $ssh_response;
    private $user_operation_factory;

    protected function setUp() : void
    {
        $this->project_manager        = Mockery::mock(\ProjectManager::class);
        $this->user_manager           = Mockery::mock(\UserManager::class);
        $this->git_repository_factory = Mockery::mock(\GitRepositoryFactory::class);
        $this->plugin                 = Mockery::mock(\gitlfsPlugin::class);
        $this->ssh_response           = Mockery::mock(SSHAuthenticateResponseBuilder::class);
        $this->user_operation_factory = \Mockery::mock(UserOperationFactory::class);

        $this->auth = new SSHAuthenticate(
            $this->project_manager,
            $this->user_manager,
            $this->git_repository_factory,
            $this->ssh_response,
            $this->user_operation_factory,
            $this->plugin
        );
    }

    public function testItFailsWhenThereAreNoArguments()
    {
        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate']);
    }

    public function testSecondArgumentIsNotAValidOperation()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')->andThrow(UnknownUserOperationException::class);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'faa.git', 'foo']);
    }

    public function test1stArgWithInvalidProjectNameMustFail()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns(\Mockery::mock(UserOperation::class));
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns(null);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function test1stArgWithNonActiveProjectMustFail()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns(\Mockery::mock(UserOperation::class));

        $project = Mockery::mock(\Project::class, ['isActive' => false]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->with('foo')->andReturns($project);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function test1stArgWithInvalidRepositoryMustFail()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns(\Mockery::mock(UserOperation::class));

        $project = Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns(null);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserNotFoundMustHaveAFailure()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns(\Mockery::mock(UserOperation::class));

        $project = Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $repository = Mockery::mock(\GitRepository::class);
        $repository->shouldNotReceive('userCanRead');
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns(null);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserNotActiveMustHaveAFailure()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns(\Mockery::mock(UserOperation::class));

        $project = Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $repository = Mockery::mock(\GitRepository::class);
        $repository->shouldNotReceive('userCanRead');
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $user = Mockery::mock(\PFUser::class, ['isAlive' => false]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserWithoutReadAccessToRepoMustHaveAFailure()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns(\Mockery::mock(UserOperation::class));

        $project = Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $user = Mockery::mock(\PFUser::class, ['isAlive' => true]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $repository = Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->with($user)->andReturns(false);
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testNoAccessWhenPluginIsNotAvailable()
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

    public function testNoAccessWhenPluginIsNotGrantedForProject()
    {
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns(\Mockery::mock(UserOperation::class));

        $project = Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $user = Mockery::mock(\PFUser::class, ['isAlive' => true]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $repository = Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->with($user)->andReturns(true);
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(false);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testItReturnsBatchResponseActionContentWhenEverythingIsOk()
    {
        $user_operation = \Mockery::mock(UserOperation::class);
        $this->user_operation_factory->shouldReceive('getUserOperationFromName')
            ->andReturns($user_operation);

        $project = Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $user = Mockery::mock(\PFUser::class, ['isAlive' => true]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $repository = Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->with($user)->andReturns(true);
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->ssh_response->shouldReceive('getResponse')->with(
            $repository,
            $user,
            $user_operation,
            Mockery::on(function ($param) {
                return $param instanceof \DateTimeImmutable;
            })
        )->andReturns(Mockery::mock(BatchResponseActionContent::class));

        $response = $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
        $this->assertInstanceOf(BatchResponseActionContent::class, $response);
    }
}
