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
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitoliteAccessURLGeneratorTest extends TestCase
{
    use ForgeConfigSandbox;

    #[\Override]
    protected function setUp(): void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
    }

    public function testGetSSHAccessTypeShouldUseGitoliteSshUser(): void
    {
        ForgeConfig::set(GitoliteAccessURLGenerator::SSH_URL, 'ssh://gitolite@%server_name%/');
        ForgeConfig::set(GitoliteAccessURLGenerator::HTTP_URL, 'https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator();

        $project    = ProjectTestBuilder::aProject()->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->build();

        $url = $access_url_generator->getSSHURL($repository);

        self::assertMatchesRegularExpression('%^ssh://gitolite@%', $url);
    }

    public function testGetAccessTypeShouldIncludesRepositoryFullName(): void
    {
        ForgeConfig::set(GitoliteAccessURLGenerator::SSH_URL, 'ssh://gitolite@%server_name%/');
        ForgeConfig::set(GitoliteAccessURLGenerator::HTTP_URL, 'https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator();

        $project    = ProjectTestBuilder::aProject()->withUnixName('gpig')->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->withName('u/johndoe/uber/bionic')->build();

        $ssh_url  = $access_url_generator->getSSHURL($repository);
        $http_url = $access_url_generator->getHTTPURL($repository);

        self::assertMatchesRegularExpression('%/gpig/u/johndoe/uber/bionic\.git$%', $ssh_url);
        self::assertMatchesRegularExpression('%/gpig/u/johndoe/uber/bionic\.git$%', $http_url);
    }

    public function testSSHURLIsEmptyWhenParameterIsSetToEmpty(): void
    {
        ForgeConfig::set(GitoliteAccessURLGenerator::SSH_URL, '');
        ForgeConfig::set(GitoliteAccessURLGenerator::HTTP_URL, 'https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator();

        $repository = GitRepositoryTestBuilder::aProjectRepository()->build();
        $ssh_url    = $access_url_generator->getSSHURL($repository);

        self::assertEquals('', $ssh_url);
    }

    public function testGetSSHAccessWorksWithCustomSSHURL(): void
    {
        ForgeConfig::set(GitoliteAccessURLGenerator::SSH_URL, 'ssh://git@stuf.example.com:2222');
        ForgeConfig::set(GitoliteAccessURLGenerator::HTTP_URL, 'https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator();

        $project    = ProjectTestBuilder::aProject()->withUnixName('gpig')->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->withName('bionic')->build();

        $url = $access_url_generator->getSSHURL($repository);

        self::assertEquals('ssh://git@stuf.example.com:2222/gpig/bionic.git', $url);
    }

    public function testServerNameIsReplaced(): void
    {
        ForgeConfig::set(GitoliteAccessURLGenerator::SSH_URL, 'ssh://gitolite@%server_name%/');
        ForgeConfig::set(GitoliteAccessURLGenerator::HTTP_URL, 'https://%server_name%/plugins/git');

        $access_url_generator = new GitoliteAccessURLGenerator();

        $project    = ProjectTestBuilder::aProject()->withUnixName('gpig')->build();
        $repository = GitRepositoryTestBuilder::aProjectRepository()->inProject($project)->withName('u/johndoe/uber/bionic')->build();

        $ssh_url  = $access_url_generator->getSSHURL($repository);
        $http_url = $access_url_generator->getHTTPURL($repository);

        self::assertMatchesRegularExpression('%^ssh://gitolite@example.com%', $ssh_url);
        self::assertMatchesRegularExpression('%^https://example.com%', $http_url);
    }
}
