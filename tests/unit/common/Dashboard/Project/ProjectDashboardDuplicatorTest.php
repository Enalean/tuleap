<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use Tuleap\Project\MappingRegistry;

class ProjectDashboardDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ProjectDashboardDuplicator
     */
    private $duplicator;

    /**
     * @var Project
     */
    private $template_project;

    /**
     * @var Project
     */
    private $new_project;

    /**
     * @var MockObject&DisabledProjectWidgetsChecker
     */
    private $checker;
    private ProjectDashboardDao&MockObject $dao;
    private ProjectDashboardRetriever&MockObject $retriever;
    private \Tuleap\Dashboard\Widget\DashboardWidgetDao&MockObject $widget_dao;
    private \Tuleap\Dashboard\Widget\DashboardWidgetRetriever&MockObject $widget_retriever;
    private \Tuleap\Widget\WidgetFactory&MockObject $widget_factory;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);
        $this->dao->method('startTransaction');
        $this->dao->method('commit');
        $this->dao->method('duplicateDashboard');
        $this->retriever  = $this->createMock(\Tuleap\Dashboard\Project\ProjectDashboardRetriever::class);
        $this->widget_dao = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $this->widget_dao->method('duplicateLine');
        $this->widget_dao->method('duplicateColumn');
        $this->widget_retriever = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetRetriever::class);
        $this->widget_factory   = $this->createMock(\Tuleap\Widget\WidgetFactory::class);
        $this->checker          = $this->createMock(DisabledProjectWidgetsChecker::class);

        $this->duplicator = new ProjectDashboardDuplicator(
            $this->dao,
            $this->retriever,
            $this->widget_dao,
            $this->widget_retriever,
            $this->widget_factory,
            $this->checker
        );

        $this->template_project = $this->createMock(\Project::class);
        $this->template_project->method('getID')->willReturn(101);
        $this->new_project = $this->createMock(\Project::class);
        $this->new_project->method('getID')->willReturn(102);
    }

    public function testItDuplicatesEachDashboards(): void
    {
        $dashboard_01 = new ProjectDashboard(1, 101, 'dashboard');
        $dashboard_02 = new ProjectDashboard(2, 101, 'dashboard 2');

        $this->widget_retriever->method('getAllWidgets')->willReturn([]);

        $this->retriever->method('getAllProjectDashboards')->with($this->template_project)->willReturn([$dashboard_01, $dashboard_02]);

        $this->dao->expects(self::exactly(2))->method('duplicateDashboard');

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDuplicatesEachLinesForADashboard(): void
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->method('getAllProjectDashboards')->with($this->template_project)->willReturn([$dashboard]);

        $line_01 = new DashboardWidgetLine(1, 'one-column', []);
        $line_02 = new DashboardWidgetLine(2, 'one-column', []);

        $this->widget_retriever->method('getAllWidgets')->with(1, 'project')->willReturn([$line_01, $line_02]);

        $this->widget_dao->expects(self::exactly(2))->method('duplicateLine');

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDuplicatesEachColumnsForALine(): void
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->method('getAllProjectDashboards')->with($this->template_project)->willReturn([$dashboard]);

        $column_01 = new DashboardWidgetColumn(1, 1, []);
        $column_02 = new DashboardWidgetColumn(2, 2, []);

        $line = new DashboardWidgetLine(
            1,
            'two-columns',
            [$column_01, $column_02]
        );

        $this->widget_retriever->method('getAllWidgets')->with(1, 'project')->willReturn([$line]);

        $this->widget_dao->expects(self::exactly(2))->method('duplicateColumn');

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDuplicatesEachWidgetForAColumn(): void
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->method('getAllProjectDashboards')->with($this->template_project)->willReturn([$dashboard]);

        $widget_01 = new DashboardWidget(1, 'projectimageviewer', 1, 1, 1, 0);
        $widget_02 = new DashboardWidget(2, 'projectcontacts', 0, 1, 2, 0);

        $column = new DashboardWidgetColumn(1, 1, [$widget_01, $widget_02]);
        $line   = new DashboardWidgetLine(
            1,
            'one-column',
            [$column]
        );

        $this->widget_retriever->method('getAllWidgets')->with(1, 'project')->willReturn([$line]);

        $widget_instance_01 = $this->createMock(\Widget::class);
        $widget_instance_01->method('setOwner');
        $widget_instance_02 = $this->createMock(\Widget::class);
        $widget_instance_02->method('setOwner');

        $this->widget_factory->method('getInstanceByWidgetName')->withConsecutive(
            ['projectimageviewer'],
            ['projectcontacts'],
        )->willReturnOnConsecutiveCalls($widget_instance_01, $widget_instance_02);

        $this->widget_dao->expects(self::exactly(2))->method('duplicateWidget');
        $widget_instance_01->expects(self::once())->method('cloneContent');
        $widget_instance_02->expects(self::once())->method('cloneContent');

        $this->checker->method('isWidgetDisabled')
            ->withConsecutive(
                [$widget_instance_01, ProjectDashboardController::DASHBOARD_TYPE],
                [$widget_instance_02, ProjectDashboardController::DASHBOARD_TYPE],
            )->willReturn(false);

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDoesNotDuplicateDisabledProjectWidgetForAColumn(): void
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->method('getAllProjectDashboards')->with($this->template_project)->willReturn([$dashboard]);

        $widget_01 = new DashboardWidget(1, 'projectimageviewer', 1, 1, 1, 0);
        $widget_02 = new DashboardWidget(2, 'projectcontacts', 0, 1, 2, 0);

        $column = new DashboardWidgetColumn(1, 1, [$widget_01, $widget_02]);
        $line   = new DashboardWidgetLine(
            1,
            'one-column',
            [$column]
        );

        $this->widget_retriever->method('getAllWidgets')->with(1, 'project')->willReturn([$line]);

        $widget_instance_01 = $this->createMock(\Widget::class);
        $widget_instance_01->method('setOwner');
        $widget_instance_02 = $this->createMock(\Widget::class);

        $this->widget_factory->method('getInstanceByWidgetName')->withConsecutive(
            ['projectimageviewer'],
            ['projectcontacts'],
        )->willReturnOnConsecutiveCalls($widget_instance_01, $widget_instance_02);

        $this->widget_dao->expects(self::once())->method('duplicateWidget');
        $widget_instance_01->expects(self::once())->method('cloneContent');
        $widget_instance_02->expects(self::never())->method('cloneContent');

        $this->checker->method('isWidgetDisabled')
            ->withConsecutive(
                [$widget_instance_01, ProjectDashboardController::DASHBOARD_TYPE],
                [$widget_instance_02, ProjectDashboardController::DASHBOARD_TYPE],
            )->willReturnOnConsecutiveCalls(false, true);

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }

    public function testItDoesNotDuplicateUnknownWidgetForAColumn(): void
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        $this->retriever->method('getAllProjectDashboards')->with($this->template_project)->willReturn([$dashboard]);

        $widget = new DashboardWidget(1, 'projectimageviewer', 1, 1, 1, 0);

        $column = new DashboardWidgetColumn(1, 1, [$widget]);
        $line   = new DashboardWidgetLine(
            1,
            'one-column',
            [$column]
        );

        $this->widget_retriever->method('getAllWidgets')->with(1, 'project')->willReturn([$line]);

        $this->widget_factory->method('getInstanceByWidgetName')->with('projectimageviewer')->willReturn(null);

        $this->widget_dao->expects(self::never())->method('duplicateWidget');

        $this->checker->expects(self::never())->method('isWidgetDisabled');

        $this->duplicator->duplicate($this->template_project, $this->new_project, new MappingRegistry([]));
    }
}
