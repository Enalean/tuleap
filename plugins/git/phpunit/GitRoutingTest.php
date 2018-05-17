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

namespace Tuleap\Git;

require_once __DIR__.'/bootstrap.php';

use Git_Mirror_MirrorDataMapper;
use Git_RemoteServer_GerritServerFactory;
use GitPermissionsManager;
use GitPlugin;
use GitRepositoryFactory;
use Logger;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use Tuleap\Git\History\GitPhpAccessLogger;
use Tuleap\Request\CollectRoutesEvent;
use FastRoute;
use Mockery;

/**
 * @group GitRoutingTest
 */
class GitRoutingTest extends TestCase
{
    public function smartHTTPRoutesProvider()
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

    /**
     * @dataProvider smartHTTPRoutesProvider
     */
    public function testSmartURLs($method, $uri)
    {
        $this->runTestOnURL($method, $uri, FastRoute\Dispatcher::FOUND, HTTP\HTTPController::class);
    }

    public function legacyRepositoryBrowsingURLs()
    {
        return [
            ['GET', '/plugins/git/index.php/101/view/26/'],
            ['GET', '/plugins/git/index.php/101/view/60/?p=tuleap%2Ftuleap.git&a=commitdiff&h=7a3aba2722e40407c90c532f37f5570ef2aa6ff5'],
            ['GET', '/plugins/git/index.php/101/view/60/?p=tuleap%2Ftuleap.git&a=tree&h=194903e670fd8af2e5f4345912393edcfa1ae84b&hb=7a3aba2722e40407c90c532f37f5570ef2aa6ff5'],
            ['GET', '/plugins/git/index.php/101/view/60/?p=tuleap%2Ftuleap.git&a=snapshot&h=3557f3589b3d107592f1a9c47e2930c59c9f4133&f=plugins&noheader=1'],
        ];
    }

    /**
     * @dataProvider legacyRepositoryBrowsingURLs
     */
    public function testLegacyRepositoryBrowsingURLsHandledByRedirectController($method, $uri)
    {
        $this->runTestOnURL($method, $uri, FastRoute\Dispatcher::FOUND, GitLegacyURLRedirectController::class);
    }

    public function friendlyURLsProvider()
    {
        return [
            ['GET', '/plugins/git/gpig/repo'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish'],
            ['GET', '/plugins/git/gpig/device/generic/goldfish'],
            ['GET', '/plugins/git/mozilla/tuleap/tuleap?p=tuleap%2Ftuleap.git&a=commitdiff&h=91018a6045ad5c5d49ec7bc0e2e2c607e5aac41d']
        ];
    }

    /**
     * @dataProvider friendlyURLsProvider
     */
    public function testFriendlyURLs($method, $uri)
    {
        $this->runTestOnURL($method, $uri, FastRoute\Dispatcher::FOUND, GitRepositoryBrowserController::class);
    }

    public function legacyGitGodControllerURLsProvider()
    {
        return [
            ['GET', '/plugins/git/?group_id=101'],
            ['GET', '/plugins/git/?action=repo_management&group_id=101&repo_id=60'],
        ];
    }

    /**
     * @dataProvider legacyGitGodControllerURLsProvider
     */
    public function testLegacyUrls($method, $uri)
    {
        $this->runTestOnURL($method, $uri, FastRoute\Dispatcher::FOUND, GitPluginDefaultController::class);
    }

    private function runTestOnURL($method, $uri, $expected_dispatch_status, $expected_dispatch_handler)
    {
        $dispatcher = \FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $route_collector) {
            $git_plugin = Mockery::mock(GitPlugin::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $git_plugin->shouldReceive([
                'getRepositoryFactory'      => \Mockery::mock(GitRepositoryFactory::class),
                'getChainOfRouters'         => \Mockery::mock(RouterLink::class),
                'getLogger'                 => \Mockery::mock(Logger::class),
                'getGerritServerFactory'    => \Mockery::mock(Git_RemoteServer_GerritServerFactory::class),
                'getPermissionsManager'     => \Mockery::mock(PermissionsManager::class),
                'getMirrorDataMapper'       => \Mockery::mock(Git_Mirror_MirrorDataMapper::class),
                'getGitPhpAccessLogger'     => \Mockery::mock(GitPhpAccessLogger::class),
                'getGitPermissionsManager'  => \Mockery::mock(GitPermissionsManager::class),
                'getConfigurationParameter' => 'foo',
            ]);

            $event = new CollectRoutesEvent($route_collector);

            $git_plugin->collectRoutesEvent($event);
        });

        $route_info = $dispatcher->dispatch($method, $uri);

        $this->assertEquals($expected_dispatch_status, $route_info[0]);
        $this->assertInstanceOf($expected_dispatch_handler, $route_info[1]());
    }
}
