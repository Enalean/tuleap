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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Dashboard\User\UserDashboard;
use Widget;

class DisabledProjectWidgetsCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DisabledProjectWidgetsDao
     */
    private $dao;

    /**
     * @var DisabledProjectWidgetsChecker
     */
    private $checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Widget
     */
    private $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao     = Mockery::mock(DisabledProjectWidgetsDao::class);
        $this->checker = new DisabledProjectWidgetsChecker($this->dao);

        $this->widget = Mockery::mock(Widget::class)->shouldReceive('getId')->andReturn('widget01')->getMock();
    }

    public function testItReturnsFalseIfDashboardTypeIsNotProject()
    {
        $this->dao->shouldNotReceive('isWidgetDisabled');
        $this->assertFalse($this->checker->isWidgetDisabled($this->widget, 'whatever'));

        $dashboard = new UserDashboard(1, 101, 'dash');
        $this->assertFalse($this->checker->checkWidgetIsDisabledFromDashboard($this->widget, $dashboard));
    }

    public function testItReturnsTrueIfDashboardTypeIsProjectAndWidgetIsInDB()
    {
        $this->dao->shouldReceive('isWidgetDisabled')->with('widget01')->andReturnTrue();
        $this->assertTrue($this->checker->isWidgetDisabled($this->widget, 'project'));
        $this->assertTrue($this->checker->isWidgetDisabled($this->widget, 'g'));

        $dashboard = new ProjectDashboard(1, 101, 'dash');
        $this->assertTrue($this->checker->checkWidgetIsDisabledFromDashboard($this->widget, $dashboard));
    }

    public function testItReturnsFalseIfDashboardTypeIsProjectAndWidgetIsNotInDB()
    {
        $this->dao->shouldReceive('isWidgetDisabled')->with('widget01')->andReturnFalse();
        $this->assertFalse($this->checker->isWidgetDisabled($this->widget, 'project'));
        $this->assertFalse($this->checker->isWidgetDisabled($this->widget, 'g'));

        $dashboard = new UserDashboard(1, 101, 'dash');
        $this->assertFalse($this->checker->checkWidgetIsDisabledFromDashboard($this->widget, $dashboard));
    }
}
