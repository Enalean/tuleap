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

namespace Tuleap\Git;

use FastRoute;
use Git_RemoteServer_GerritServerFactory;
use GitDao;
use GitPermissionsManager;
use GitPlugin;
use GitRepositoryFactory;
use PermissionsManager;
use Psr\Log\NullLogger;
use Tuleap\Git\History\GitPhpAccessLogger;
use Tuleap\Git\Repository\GitRepositoryHeaderDisplayer;
use Tuleap\Git\RepositoryList\GitRepositoryListController;
use Tuleap\GlobalResponseMock;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Test\PHPUnit\TestCase;
use UserDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRoutingTest extends TestCase
{
    use GlobalResponseMock;

    public static function smartHTTPRoutesProvider(): array
    {
        return [
            ['GET', '/plugins/git/gpig/goldfish/HEAD'],
            ['GET', '/plugins/git/gpig/goldfish.git/HEAD'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish.git/HEAD'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish/info/refs?service=git-upload-pack'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish.git/info/refs?service=git-upload-pack'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish.git/git-upload-pack'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish.git/git-receive-pack'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish.git/objects/f5/30d381822b12f76923bfba729fead27b378bec'],
            ['GET', '/plugins/git/gpig/apache-2.5.git/HEAD'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('smartHTTPRoutesProvider')]
    public function testSmartURLs($method, $uri): void
    {
        $this->runTestOnURL($method, $uri, HTTP\HTTPController::class);
    }

    public static function legacyRepositoryBrowsingURLs(): array
    {
        return [
            ['GET', '/plugins/git/index.php/101/view/26/'],
            ['GET', '/plugins/git/index.php/101/view/60/?p=tuleap%2Ftuleap.git&a=commitdiff&h=7a3aba2722e40407c90c532f37f5570ef2aa6ff5'],
            ['GET', '/plugins/git/index.php/101/view/60/?p=tuleap%2Ftuleap.git&a=tree&h=194903e670fd8af2e5f4345912393edcfa1ae84b&hb=7a3aba2722e40407c90c532f37f5570ef2aa6ff5'],
            ['GET', '/plugins/git/index.php/101/view/60/?p=tuleap%2Ftuleap.git&a=snapshot&h=3557f3589b3d107592f1a9c47e2930c59c9f4133&f=plugins&noheader=1'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('legacyRepositoryBrowsingURLs')]
    public function testLegacyRepositoryBrowsingURLsHandledByRedirectController($method, $uri): void
    {
        $this->runTestOnURL($method, $uri, GitLegacyURLRedirectController::class);
    }

    public static function friendlyURLsProvider(): array
    {
        return [
            ['GET', '/plugins/git/gpig/repo'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish'],
            ['GET', '/plugins/git/mozilla/tuleap/tuleap?p=tuleap%2Ftuleap.git&a=commitdiff&h=91018a6045ad5c5d49ec7bc0e2e2c607e5aac41d'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('friendlyURLsProvider')]
    public function testFriendlyURLs($method, $uri): void
    {
        $this->runTestOnURL($method, $uri, GitRepositoryBrowserController::class);
    }

    public static function friendlyProjectURLsProvider(): array
    {
        return [
            ['GET', '/plugins/git/gpig/'],
            ['GET', '/plugins/git/gpig'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('friendlyProjectURLsProvider')]
    public function testFriendlyProjectURLs($method, $uri): void
    {
        $this->runTestOnURL($method, $uri, GitRepositoryListController::class);
    }

    public static function legacyGitGodControllerURLsProvider(): array
    {
        return [
            ['GET', '/plugins/git/?group_id=101'],
            ['GET', '/plugins/git/?action=repo_management&group_id=101&repo_id=60'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('legacyGitGodControllerURLsProvider')]
    public function testLegacyUrls($method, $uri): void
    {
        $this->runTestOnURL($method, $uri, GitPluginDefaultController::class);
    }

    /**
     * @param class-string $expected_dispatch_handler
     */
    private function runTestOnURL(string $method, string $uri, string $expected_dispatch_handler): void
    {
        $git_plugin = $this->createPartialMock(
            GitPlugin::class,
            [
                'getRepositoryFactory',
                'getChainOfRouters',
                'getLogger',
                'getGerritServerFactory',
                'getPermissionsManager',
                'getGitPhpAccessLogger',
                'getGitPermissionsManager',
                'getUserDao',
                'getGitDao',
                'getHeaderRenderer',
                'getThemeManager',
                'getGitRepositoryHeaderDisplayer',
                'getName',
            ]
        );
        $git_plugin->method('getRepositoryFactory')->willReturn($this->createMock(GitRepositoryFactory::class));
        $git_plugin->method('getChainOfRouters')->willReturn($this->createMock(RouterLink::class));
        $git_plugin->method('getGerritServerFactory')->willReturn($this->createMock(Git_RemoteServer_GerritServerFactory::class));
        $git_plugin->method('getPermissionsManager')->willReturn($this->createMock(PermissionsManager::class));
        $git_plugin->method('getGitPhpAccessLogger')->willReturn($this->createMock(GitPhpAccessLogger::class));
        $git_plugin->method('getGitPermissionsManager')->willReturn($this->createMock(GitPermissionsManager::class));
        $git_plugin->method('getUserDao')->willReturn($this->createMock(UserDao::class));
        $git_plugin->method('getGitDao')->willReturn($this->createMock(GitDao::class));
        $git_plugin->method('getLogger')->willReturn(new NullLogger());
        $git_plugin->method('getGitRepositoryHeaderDisplayer')->willReturn($this->createMock(GitRepositoryHeaderDisplayer::class));
        $git_plugin->method('getName')->willReturn('git');

        $dispatcher = \FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $route_collector) use ($git_plugin) {
            $event = new CollectRoutesEvent($route_collector);

            $git_plugin->collectRoutesEvent($event);
        });

        $route_info = $dispatcher->dispatch($method, $uri);

        self::assertEquals(FastRoute\Dispatcher::FOUND, $route_info[0]);
        $handler_name = $route_info[1]['handler'];
        $controller   = $git_plugin->$handler_name();
        self::assertInstanceOf($expected_dispatch_handler, $controller);
    }
}
