<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Permission\Fields\ByGroup;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use TemplateRenderer;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\TemplateRendererStub;
use Tuleap\Tracker\Permission\Fields\ByField\ByFieldController;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsOnFieldsTest extends TestCase
{
    /**
     * @param class-string<ByFieldController|ByGroupController> $controller_class_name
     */
    #[DataProvider('controllerProvider')]
    public function testAdminCanDisplay(string $controller_class_name): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $user    = UserTestBuilder::aUser()->withMemberOf($project)->withoutSiteAdministrator()->build();
        $request = HTTPRequestBuilder::get()->withUser($user)->build();

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withUserIsAdmin(true)
            ->build();

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn($tracker);

        $renderer = new TemplateRendererStub();
        $layout   = new TestLayout(new LayoutInspector());

        $controller = $this->getController($controller_class_name, $tracker_factory, $renderer);

        $controller->expects($this->once())->method('display')->with($tracker, $request, $layout);

        $controller->process($request, $layout, ['id' => 23]);
    }

    /**
     * @param class-string<ByFieldController|ByGroupController> $controller_class_name
     */
    #[DataProvider('controllerProvider')]
    public function testNonAdminGetsBlocked(string $controller_class_name): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $user    = UserTestBuilder::aUser()->withMemberOf($project)->withoutSiteAdministrator()->build();
        $request = HTTPRequestBuilder::get()->withUser($user)->build();

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withUserIsAdmin(false)
            ->build();

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn($tracker);

        $renderer = $this->createMock(TemplateRenderer::class);
        $layout   = $this->createMock(BaseLayout::class);

        $controller = $this->getController($controller_class_name, $tracker_factory, $renderer);

        $this->expectException(ForbiddenException::class);

        $controller->process($request, $layout, ['id' => 23]);
    }

    /**
     * @param class-string<ByFieldController|ByGroupController> $controller_class_name
     */
    #[DataProvider('controllerProvider')]
    public function testTrackerWasDeleted(string $controller_class_name): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $user    = UserTestBuilder::aUser()->withMemberOf($project)->withoutSiteAdministrator()->build();
        $request = HTTPRequestBuilder::get()->withUser($user)->build();

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject($project)
            ->withDeletionDate(1234567890)
            ->withUserIsAdmin(true)
            ->build();

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn($tracker);

        $renderer = $this->createMock(TemplateRenderer::class);
        $layout   = $this->createMock(BaseLayout::class);

        $controller = $this->getController($controller_class_name, $tracker_factory, $renderer);

        $this->expectException(NotFoundException::class);

        $controller->process($request, $layout, ['id' => 23]);
    }

    /**
     * @param class-string<ByFieldController|ByGroupController> $controller_class_name
     */
    #[DataProvider('controllerProvider')]
    public function testTrackerWasNotFound(string $controller_class_name): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $request = HTTPRequestBuilder::get()->withUser($user)->build();

        $tracker_factory = $this->createMock(TrackerFactory::class);
        $tracker_factory->method('getTrackerById')->willReturn(null);

        $renderer = $this->createMock(TemplateRenderer::class);
        $layout   = $this->createMock(BaseLayout::class);

        $controller = $this->getController($controller_class_name, $tracker_factory, $renderer);

        $this->expectException(NotFoundException::class);

        $controller->process($request, $layout, ['id' => 23]);
    }

    /**
     * @param class-string<ByFieldController|ByGroupController> $controller_class_name
     * @return MockObject&ByFieldController|MockObject&ByGroupController
     */
    private function getController(
        string $controller_class_name,
        TrackerFactory $tracker_factory,
        TemplateRenderer $renderer,
    ): MockObject {
        return $this->getMockBuilder($controller_class_name)
            ->setConstructorArgs([$tracker_factory, $renderer])
            ->onlyMethods(['display'])
            ->getMock();
    }

    /**
     * @return class-string<ByFieldController|ByGroupController>[][]
     */
    public static function controllerProvider(): array
    {
        return [
            [ ByFieldController::class ],
            [ ByGroupController::class ],
        ];
    }
}
