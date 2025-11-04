<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

use Git;
use GitPlugin;
use HTTPRequest;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Git\Tests\Stub\RouterLinkStub;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[DisableReturnValueGenerationForTestDoubles]
final class GitPluginDefaultControllerTest extends TestCase
{
    private RouterLinkStub $router_link;
    private EventDispatcherStub $event_dispatcher;

    #[Override]
    protected function setUp(): void
    {
        $this->router_link      = RouterLinkStub::buildPassThrough();
        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();
    }

    private function isBurningParrot(HTTPRequest $request): bool
    {
        $controller = new GitPluginDefaultController(
            RouterLinkStub::buildPassThrough(),
            EventDispatcherStub::withIdentityCallback()
        );

        return $controller->isInABurningParrotPage($request, []);
    }

    public static function generateBurningParrotURIs(): iterable
    {
        yield [Git::ADMIN_GIT_ADMINS_ACTION];
    }

    #[DataProvider('generateBurningParrotURIs')]
    public function testIsInABurningParrotPage(string $action): void
    {
        $request = HTTPRequestBuilder::get()->withParam('action', $action)->build();
        self::assertTrue($this->isBurningParrot($request));
    }

    public static function generateNotBurningParrotURIs(): iterable
    {
        yield ['repo_management'];
        yield ['fork'];
        yield ['confirm_private'];
        yield ['fork_repositories'];
        yield ['fetch_git_config'];
        yield ['fetch_git_template'];
        yield ['fork_repositories_permissions'];
        yield ['view_last_git_pushes'];
        yield ['admin'];
        yield ['admin-gerrit-templates'];
    }

    #[DataProvider('generateNotBurningParrotURIs')]
    public function testIsNotBurningParrot(string $action): void
    {
        $request = HTTPRequestBuilder::get()->withParam('action', $action)->build();
        self::assertFalse($this->isBurningParrot($request));
    }

    private function process(HTTPRequest $request): void
    {
        $controller = new GitPluginDefaultController(
            $this->router_link,
            $this->event_dispatcher
        );
        $controller->process($request, LayoutBuilder::build(), []);
    }

    public function testItThrowsExceptionIfTheServiceIsNotActive(): void
    {
        $this->expectException(NotFoundException::class);

        $request = HTTPRequestBuilder::get()->withParam('action', 'admin')->withProject(
            ProjectTestBuilder::aProject()->withoutServices()->build()
        )->build();

        $this->process($request);
    }

    public function testItProcessesTheRequest(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $project->addUsedServices(GitPlugin::SERVICE_SHORTNAME);

        $request = HTTPRequestBuilder::get()->withParam('action', 'admin')->withProject(
            $project
        )->build();

        $this->process($request);
        self::assertSame(1, $this->event_dispatcher->getCallCount());
        self::assertSame(1, $this->router_link->getCallCount());
    }
}
