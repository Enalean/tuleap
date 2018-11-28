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

require_once __DIR__.'/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SSHAuthenticateTest extends TestCase
{

    use MockeryPHPUnitIntegration;

    private $auth;
    private $git_repository_factory;
    private $project_manager;
    private $user_manager;
    private $plugin;

    protected function setUp()
    {
        parent::setUp();

        $this->project_manager = \Mockery::mock(\ProjectManager::class);
        $this->user_manager = \Mockery::mock(\UserManager::class);
        $this->git_repository_factory = \Mockery::mock(\GitRepositoryFactory::class);
        $this->plugin = \Mockery::mock(\gitlfsPlugin::class);

        $this->auth = new SSHAuthenticate(
            $this->project_manager,
            $this->user_manager,
            $this->git_repository_factory,
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
        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'faa.git', 'foo']);
    }

    public function test1stArgWithInvalidProjectNameMustFail()
    {
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns(null);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function test1stArgWithNonActiveProjectMustFail()
    {
        $project = \Mockery::mock(\Project::class, ['isActive' => false ]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->with('foo')->andReturns($project);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function test1stArgWithInvalidRepositoryMustFail()
    {
        $project = \Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122 ]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns(null);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserNotFoundMustHaveAFailure()
    {
        $project = \Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122 ]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldNotReceive('userCanRead');
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns(null);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserNotActiveMustHaveAFailure()
    {
        $project = \Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122 ]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldNotReceive('userCanRead');
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $user = \Mockery::mock(\PFUser::class, ['isAlive' => false]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testUserWithoutReadAccessToRepoMustHaveAFailure()
    {
        $project = \Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122 ]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $user = \Mockery::mock(\PFUser::class, ['isAlive' => true]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $repository = \Mockery::mock(\GitRepository::class);
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
            null
        );

        $this->expectException(InvalidCommandException::class);

        $auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testNoAccessWhenPluginIsNotGrantedForProject()
    {
        $project = \Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122 ]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $user = \Mockery::mock(\PFUser::class, ['isAlive' => true]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->with($user)->andReturns(true);
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(false);

        $this->expectException(InvalidCommandException::class);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }

    public function testEnvIsOk()
    {
        $project = \Mockery::mock(\Project::class, ['isActive' => true, 'getID' => 122 ]);
        $this->project_manager->shouldReceive('getProjectByCaseInsensitiveUnixName')->andReturns($project);

        $user = \Mockery::mock(\PFUser::class, ['isAlive' => true]);
        $this->user_manager->shouldReceive('getUserByUserName')->with('mary')->andReturns($user);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('userCanRead')->with($user)->andReturns(true);
        $this->git_repository_factory->shouldReceive('getRepositoryByPath')->with(122, 'foo/faa.git')->andReturns($repository);

        $this->plugin->shouldReceive('isAllowed')->with(122)->andReturns(true);

        $this->auth->main('mary', ['/usr/share/gitolite3/commands/git-lfs-authenticate', 'foo/faa.git', 'download']);
    }
}
