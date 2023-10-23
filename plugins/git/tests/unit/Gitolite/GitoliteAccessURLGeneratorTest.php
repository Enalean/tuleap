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
 */

namespace Tuleap\Git\Gitolite;


require_once __DIR__ . '/../bootstrap.php';

class GitoliteAccessURLGeneratorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        \ForgeConfig::store();
        \ForgeConfig::set('sys_default_domain', 'example.com');
    }

    protected function tearDown(): void
    {
        \ForgeConfig::restore();
    }

    public function testGetSSHAccessTypeShouldUseGitoliteSshUser()
    {
        $git_plugin_info = \Mockery::mock(\GitPluginInfo::class);
        $git_plugin_info->shouldReceive('getPropertyValueForName')->with('git_ssh_url');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName');
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getProject')->andReturns($project);
        $repository->shouldReceive('getFullName');

        $url = $access_url_generator->getSSHURL($repository);

        $this->assertMatchesRegularExpression('%^ssh://gitolite@%', $url);
    }

    public function testGetAccessTypeShouldIncludesRepositoryFullName()
    {
        $git_plugin_info = \Mockery::mock(\GitPluginInfo::class);
        $git_plugin_info->shouldReceive('getPropertyValueForName')->with('git_ssh_url');
        $git_plugin_info->shouldReceive('getPropertyValueForName')->with('git_http_url')
            ->andReturns('https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('gpig');
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getProject')->andReturns($project);
        $repository->shouldReceive('getFullName')->andReturns('u/johndoe/uber/bionic');

        $ssh_url  = $access_url_generator->getSSHURL($repository);
        $http_url = $access_url_generator->getHTTPURL($repository);

        $this->assertMatchesRegularExpression('%/gpig/u/johndoe/uber/bionic\.git$%', $ssh_url);
        $this->assertMatchesRegularExpression('%/gpig/u/johndoe/uber/bionic\.git$%', $http_url);
    }

    public function testSSHURLIsEmptyWhenParameterIsSetToEmpty()
    {
        $git_plugin_info = \Mockery::mock(\GitPluginInfo::class);
        $git_plugin_info->shouldReceive('getPropertyValueForName')->with('git_ssh_url')->andReturns('');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $repository = \Mockery::mock(\GitRepository::class);
        $ssh_url    = $access_url_generator->getSSHURL($repository);

        $this->assertEquals('', $ssh_url);
    }

    public function testGetSSHAccessWorksWithCustomSSHURL()
    {
        $git_plugin_info = \Mockery::mock(\GitPluginInfo::class);
        $git_plugin_info->shouldReceive('getPropertyValueForName')->with('git_ssh_url')
            ->andReturns('ssh://git@stuf.example.com:2222');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('gpig');
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getProject')->andReturns($project);
        $repository->shouldReceive('getFullName')->andReturns('bionic');

        $url = $access_url_generator->getSSHURL($repository);

        $this->assertEquals('ssh://git@stuf.example.com:2222/gpig/bionic.git', $url);
    }

    public function testServerNameIsReplaced()
    {
        $git_plugin_info = \Mockery::mock(\GitPluginInfo::class);
        $git_plugin_info->shouldReceive('getPropertyValueForName')->with('git_ssh_url');
        $git_plugin_info->shouldReceive('getPropertyValueForName')->with('git_http_url')
            ->andReturns('https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('gpig');
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getProject')->andReturns($project);
        $repository->shouldReceive('getFullName')->andReturns('u/johndoe/uber/bionic');

        $ssh_url  = $access_url_generator->getSSHURL($repository);
        $http_url = $access_url_generator->getHTTPURL($repository);

        $this->assertMatchesRegularExpression('%^ssh://gitolite@example.com%', $ssh_url);
        $this->assertMatchesRegularExpression('%^https://example.com%', $http_url);
    }
}
