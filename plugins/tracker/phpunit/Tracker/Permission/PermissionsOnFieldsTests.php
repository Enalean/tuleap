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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Permission\Fields\ByField\ByFieldController;

class PermissionsOnFieldsTests extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider controllerProvider
     */
    public function testAdminCanDisplay(string $controller_class_name)
    {
        $request = \Mockery::mock(\HTTPRequest::class, ['getCurrentUser' => \Mockery::mock(\PFUser::class)]);
        $tracker = \Mockery::mock(\Tracker::class, ['isActive' => true, 'userIsAdmin' => true]);
        $tracker_factory = \Mockery::mock(\TrackerFactory::class, ['getTrackerById' => $tracker]);
        $controller = \Mockery::mock($controller_class_name, [$tracker_factory])->makePartial()->shouldAllowMockingProtectedMethods();

        $controller->shouldReceive('display')->with($tracker, $request)->once();

        $controller->process($request, \Mockery::mock(BaseLayout::class), ['id' => 23]);
    }

    /**
     * @dataProvider controllerProvider
     */
    public function testNonAdminGetsBlocked(string $controller_class_name)
    {
        $request = \Mockery::mock(\HTTPRequest::class, ['getCurrentUser' => \Mockery::mock(\PFUser::class)]);
        $tracker = \Mockery::mock(\Tracker::class, ['isActive' => true, 'userIsAdmin' => false]);
        $tracker_factory = \Mockery::mock(\TrackerFactory::class, ['getTrackerById' => $tracker]);
        $controller = \Mockery::mock($controller_class_name, [$tracker_factory])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->expectException(ForbiddenException::class);

        $controller->process($request, \Mockery::mock(BaseLayout::class), ['id' => 23]);
    }

    /**
     * @dataProvider controllerProvider
     */
    public function testTrackerWasDeleted(string $controller_class_name)
    {
        $request = \Mockery::mock(\HTTPRequest::class, ['getCurrentUser' => \Mockery::mock(\PFUser::class)]);
        $tracker = \Mockery::mock(\Tracker::class, ['isActive' => false, 'userIsAdmin' => true]);
        $tracker_factory = \Mockery::mock(\TrackerFactory::class, ['getTrackerById' => $tracker]);
        $controller = \Mockery::mock($controller_class_name, [$tracker_factory])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->expectException(NotFoundException::class);

        $controller->process($request, \Mockery::mock(BaseLayout::class), ['id' => 23]);
    }

    /**
     * @dataProvider controllerProvider
     */
    public function testTrackerWasNotFound(string $controller_class_name)
    {
        $request = \Mockery::mock(\HTTPRequest::class, ['getCurrentUser' => \Mockery::mock(\PFUser::class)]);
        $tracker_factory = \Mockery::mock(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackerById')->with(23)->andReturns(null);
        $controller = \Mockery::mock($controller_class_name, [$tracker_factory])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->expectException(NotFoundException::class);

        $controller->process($request, \Mockery::mock(BaseLayout::class), ['id' => 23]);
    }

    public function controllerProvider() : array
    {
        return [
            [ ByFieldController::class ],
            [ ByGroupController::class ],
        ];
    }
}
