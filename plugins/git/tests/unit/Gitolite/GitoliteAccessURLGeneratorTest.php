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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use ForgeConfig;
use GitPluginInfo;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class GitoliteAccessURLGeneratorTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
    }

    public function testGetSSHAccessTypeShouldUseGitoliteSshUser(): void
    {
        $git_plugin_info = $this->createMock(GitPluginInfo::class);
        $git_plugin_info->method('getPropertyValueForName')->with('git_ssh_url');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project    = ProjectTestBuilder::aProject()->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->build();

        $url = $access_url_generator->getSSHURL($repository);

        self::assertMatchesRegularExpression('%^ssh://gitolite@%', $url);
    }

    public function testGetAccessTypeShouldIncludesRepositoryFullName(): void
    {
        $git_plugin_info = $this->createMock(GitPluginInfo::class);
        $git_plugin_info->method('getPropertyValueForName')->willReturnOnConsecutiveCalls(null, 'https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project    = ProjectTestBuilder::aProject()->withUnixName('gpig')->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->withName('u/johndoe/uber/bionic')->build();

        $ssh_url  = $access_url_generator->getSSHURL($repository);
        $http_url = $access_url_generator->getHTTPURL($repository);

        self::assertMatchesRegularExpression('%/gpig/u/johndoe/uber/bionic\.git$%', $ssh_url);
        self::assertMatchesRegularExpression('%/gpig/u/johndoe/uber/bionic\.git$%', $http_url);
    }

    public function testSSHURLIsEmptyWhenParameterIsSetToEmpty(): void
    {
        $git_plugin_info = $this->createMock(GitPluginInfo::class);
        $git_plugin_info->method('getPropertyValueForName')->with('git_ssh_url')->willReturn('');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $repository = GitRepositoryTestBuilder::aProjectRepository()->build();
        $ssh_url    = $access_url_generator->getSSHURL($repository);

        self::assertEquals('', $ssh_url);
    }

    public function testGetSSHAccessWorksWithCustomSSHURL(): void
    {
        $git_plugin_info = $this->createMock(GitPluginInfo::class);
        $git_plugin_info->method('getPropertyValueForName')->with('git_ssh_url')
            ->willReturn('ssh://git@stuf.example.com:2222');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project    = ProjectTestBuilder::aProject()->withUnixName('gpig')->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->withName('bionic')->build();

        $url = $access_url_generator->getSSHURL($repository);

        self::assertEquals('ssh://git@stuf.example.com:2222/gpig/bionic.git', $url);
    }

    public function testServerNameIsReplaced(): void
    {
        $git_plugin_info = $this->createMock(GitPluginInfo::class);
        $git_plugin_info->method('getPropertyValueForName')->willReturnOnConsecutiveCalls(null, 'https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator($git_plugin_info);

        $project    = ProjectTestBuilder::aProject()->withUnixName('gpig')->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->withName('u/johndoe/uber/bionic')->build();

        $ssh_url  = $access_url_generator->getSSHURL($repository);
        $http_url = $access_url_generator->getHTTPURL($repository);

        self::assertMatchesRegularExpression('%^ssh://gitolite@example.com%', $ssh_url);
        self::assertMatchesRegularExpression('%^https://example.com%', $http_url);
    }
}
