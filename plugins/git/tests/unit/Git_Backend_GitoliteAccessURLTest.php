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

namespace Tuleap\Git;

use Git_Backend_Gitolite;
use Git_GitoliteDriver;
use Psr\Log\NullLogger;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Git_Backend_GitoliteAccessURLTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testGetAccessURLIsEmptyWhenGenerationReturnsEmptyURLs(): void
    {
        $url_generator = $this->createMock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            $this->createMock(Git_GitoliteDriver::class),
            $url_generator,
            new DefaultBranchUpdateExecutorStub(),
            new NullLogger()
        );

        $url_generator->method('getSSHURL')->willReturn('');
        $url_generator->method('getHTTPURL')->willReturn('');

        $access_urls = $backend->getAccessURL(GitRepositoryTestBuilder::aProjectRepository()->build());

        self::assertEquals([], $access_urls);
    }

    public function testGetAccessURLWithOnlySSHURLSet(): void
    {
        $url_generator = $this->createMock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            $this->createMock(Git_GitoliteDriver::class),
            $url_generator,
            new DefaultBranchUpdateExecutorStub(),
            new NullLogger(),
        );

        $url_generator->method('getSSHURL')->willReturn('ssh://gitolite@example.com/');
        $url_generator->method('getHTTPURL')->willReturn('');

        $access_urls = $backend->getAccessURL(GitRepositoryTestBuilder::aProjectRepository()->build());

        self::assertEquals(['ssh' => 'ssh://gitolite@example.com/'], $access_urls);
    }

    public function testGetAccessURLWithOnlyHTTPURLSet(): void
    {
        $url_generator = $this->createMock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            $this->createMock(Git_GitoliteDriver::class),
            $url_generator,
            new DefaultBranchUpdateExecutorStub(),
            new NullLogger(),
        );

        $url_generator->method('getSSHURL')->willReturn('');
        $url_generator->method('getHTTPURL')->willReturn('https://example.com/');

        $access_urls = $backend->getAccessURL(GitRepositoryTestBuilder::aProjectRepository()->build());

        self::assertEquals(['http' => 'https://example.com/'], $access_urls);
    }

    public function testGetAccessURLWithSSHAndHTTPURLs(): void
    {
        $url_generator = $this->createMock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            $this->createMock(Git_GitoliteDriver::class),
            $url_generator,
            new DefaultBranchUpdateExecutorStub(),
            new NullLogger(),
        );

        $url_generator->method('getSSHURL')->willReturn('ssh://gitolite@example.com/');
        $url_generator->method('getHTTPURL')->willReturn('https://example.com/');

        $access_urls = $backend->getAccessURL(GitRepositoryTestBuilder::aProjectRepository()->build());

        self::assertEquals(['ssh' => 'ssh://gitolite@example.com/', 'http' => 'https://example.com/'], $access_urls);
    }
}
