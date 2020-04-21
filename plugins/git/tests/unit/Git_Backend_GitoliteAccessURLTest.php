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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_Backend_GitoliteAccessURLTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetAccessURLIsEmptyWhenGenerationReturnsEmptyURLs()
    {
        $url_generator = Mockery::mock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            Mockery::mock(Git_GitoliteDriver::class),
            $url_generator,
            Mockery::mock(\Psr\Log\LoggerInterface::class)
        );

        $url_generator->shouldReceive('getSSHURL')->andReturns('');
        $url_generator->shouldReceive('getHTTPURL')->andReturns('');

        $access_urls = $backend->getAccessURL(Mockery::mock(GitRepository::class));

        $this->assertEquals([], $access_urls);
    }

    public function testGetAccessURLWithOnlySSHURLSet()
    {
        $url_generator = Mockery::mock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            Mockery::mock(Git_GitoliteDriver::class),
            $url_generator,
            Mockery::mock(\Psr\Log\LoggerInterface::class)
        );

        $url_generator->shouldReceive('getSSHURL')->andReturns('ssh://gitolite@example.com/');
        $url_generator->shouldReceive('getHTTPURL')->andReturns('');

        $access_urls = $backend->getAccessURL(Mockery::mock(GitRepository::class));

        $this->assertEquals(['ssh' => 'ssh://gitolite@example.com/'], $access_urls);
    }

    public function testGetAccessURLWithOnlyHTTPURLSet()
    {
        $url_generator = Mockery::mock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            Mockery::mock(Git_GitoliteDriver::class),
            $url_generator,
            Mockery::mock(\Psr\Log\LoggerInterface::class)
        );

        $url_generator->shouldReceive('getSSHURL')->andReturns('');
        $url_generator->shouldReceive('getHTTPURL')->andReturns('https://example.com/');

        $access_urls = $backend->getAccessURL(Mockery::mock(GitRepository::class));

        $this->assertEquals(['http' => 'https://example.com/'], $access_urls);
    }

    public function testGetAccessURLWithSSHAndHTTPURLs()
    {
        $url_generator = Mockery::mock(GitoliteAccessURLGenerator::class);
        $backend       = new Git_Backend_Gitolite(
            Mockery::mock(Git_GitoliteDriver::class),
            $url_generator,
            Mockery::mock(\Psr\Log\LoggerInterface::class)
        );

        $url_generator->shouldReceive('getSSHURL')->andReturns('ssh://gitolite@example.com/');
        $url_generator->shouldReceive('getHTTPURL')->andReturns('https://example.com/');

        $access_urls = $backend->getAccessURL(Mockery::mock(GitRepository::class));

        $this->assertEquals(['ssh' => 'ssh://gitolite@example.com/', 'http' => 'https://example.com/'], $access_urls);
    }
}
