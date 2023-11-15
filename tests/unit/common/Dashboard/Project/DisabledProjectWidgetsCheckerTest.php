<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Dashboard\User\UserDashboard;
use Widget;

class DisabledProjectWidgetsCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var MockObject&DisabledProjectWidgetsDao
     */
    private $dao;

    /**
     * @var DisabledProjectWidgetsChecker
     */
    private $checker;

    /**
     * @var MockObject&Widget
     */
    private $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao     = $this->createMock(DisabledProjectWidgetsDao::class);
        $this->checker = new DisabledProjectWidgetsChecker($this->dao);

        $this->widget = $this->createMock(Widget::class);
        $this->widget->method('getId')->willReturn('widget01');
    }

    public function testItReturnsFalseIfDashboardTypeIsNotProject(): void
    {
        $this->dao->method('isWidgetDisabled');
        self::assertFalse($this->checker->isWidgetDisabled($this->widget, 'whatever'));

        $dashboard = new UserDashboard(1, 101, 'dash');
        self::assertFalse($this->checker->checkWidgetIsDisabledFromDashboard($this->widget, $dashboard));
    }

    public function testItReturnsTrueIfDashboardTypeIsProjectAndWidgetIsInDB(): void
    {
        $this->dao->method('isWidgetDisabled')->with('widget01')->willReturn(true);
        self::assertTrue($this->checker->isWidgetDisabled($this->widget, 'project'));
        self::assertTrue($this->checker->isWidgetDisabled($this->widget, 'g'));

        $dashboard = new ProjectDashboard(1, 101, 'dash');
        self::assertTrue($this->checker->checkWidgetIsDisabledFromDashboard($this->widget, $dashboard));
    }

    public function testItReturnsFalseIfDashboardTypeIsProjectAndWidgetIsNotInDB(): void
    {
        $this->dao->method('isWidgetDisabled')->with('widget01')->willReturn(false);
        self::assertFalse($this->checker->isWidgetDisabled($this->widget, 'project'));
        self::assertFalse($this->checker->isWidgetDisabled($this->widget, 'g'));

        $dashboard = new UserDashboard(1, 101, 'dash');
        self::assertFalse($this->checker->checkWidgetIsDisabledFromDashboard($this->widget, $dashboard));
    }
}
