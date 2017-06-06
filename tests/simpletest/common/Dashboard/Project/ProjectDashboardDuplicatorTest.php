<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use TuleapTestCase;

class ProjectDashboardDuplicatorTest extends TuleapTestCase
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

    public function setUp()
    {
        parent::setUp();

        $this->dao              = mock('Tuleap\Dashboard\Project\ProjectDashboardDao');
        $this->retriever        = mock('Tuleap\Dashboard\Project\ProjectDashboardRetriever');
        $this->widget_dao       = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $this->widget_retriever = mock('Tuleap\Dashboard\Widget\DashboardWidgetRetriever');
        $this->widget_factory   = mock('Tuleap\Widget\WidgetFactory');

        $this->duplicator = new ProjectDashboardDuplicator(
            $this->dao,
            $this->retriever,
            $this->widget_dao,
            $this->widget_retriever,
            $this->widget_factory
        );

        $this->template_project = aMockProject()->withId(101)->build();
        $this->new_project      = aMockProject()->withId(102)->build();
    }

    public function itDuplicatesEachDasboards()
    {
        $dashboard_01 = new ProjectDashboard(1, 101, 'dashboard');
        $dashboard_02 = new ProjectDashboard(2, 101, 'dashboard 2');

        stub($this->widget_retriever)->getAllWidgets()->returns(array());

        stub($this->retriever)->getAllProjectDashboards($this->template_project)->returns(
            array($dashboard_01, $dashboard_02)
        );

        expect($this->dao)->duplicateDashboard()->count(2);

        $this->duplicator->duplicate($this->template_project, $this->new_project);
    }

    public function itDuplicatesEachLinesForADashboard()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        stub($this->retriever)->getAllProjectDashboards($this->template_project)->returns(
            array($dashboard)
        );

        $line_01 = new DashboardWidgetLine(1, 1, 'project', 'one-column', 1, array());
        $line_02 = new DashboardWidgetLine(2, 1, 'project', 'one-column', 2, array());

        stub($this->widget_retriever)->getAllWidgets(1, 'project')->returns(
            array($line_01, $line_02)
        );

        expect($this->widget_dao)->duplicateLine()->count(2);

        $this->duplicator->duplicate($this->template_project, $this->new_project);
    }

    public function itDuplicatesEachColumnsForALine()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        stub($this->retriever)->getAllProjectDashboards($this->template_project)->returns(
            array($dashboard)
        );

        $column_01 = new DashboardWidgetColumn(1, 1, 1, array());
        $column_02 = new DashboardWidgetColumn(2, 1, 2, array());

        $line = new DashboardWidgetLine(
            1,
            1,
            'project',
            'two-columns',
            1,
            array($column_01, $column_02)
        );

        stub($this->widget_retriever)->getAllWidgets(1, 'project')->returns(
            array($line)
        );

        expect($this->widget_dao)->duplicateColumn()->count(2);

        $this->duplicator->duplicate($this->template_project, $this->new_project);
    }

    public function itDuplicatesEachWidgetForAColumn()
    {
        $dashboard = new ProjectDashboard(1, 101, 'dashboard');

        stub($this->retriever)->getAllProjectDashboards($this->template_project)->returns(
            array($dashboard)
        );

        $widget_01 = new DashboardWidget(1, 'projectimageviewer', 1, 1, 1, 0);
        $widget_02 = new DashboardWidget(2, 'projectcontacts', 0, 1, 2, 0);

        $column = new DashboardWidgetColumn(1, 1, 1, array($widget_01, $widget_02));
        $line   = new DashboardWidgetLine(
            1,
            1,
            'project',
            'one-column',
            1,
            array($column)
        );

        stub($this->widget_retriever)->getAllWidgets(1, 'project')->returns(
            array($line)
        );

        $widget_instance_01 = mock('Widget');
        $widget_instance_02 = mock('Widget');

        stub($this->widget_factory)->getInstanceByWidgetName()->returnsAt(0, $widget_instance_01);
        stub($this->widget_factory)->getInstanceByWidgetName()->returnsAt(1, $widget_instance_02);

        expect($this->widget_dao)->duplicateWidget()->count(2);
        expect($widget_instance_01)->cloneContent()->once();
        expect($widget_instance_02)->cloneContent()->once();

        $this->duplicator->duplicate($this->template_project, $this->new_project);
    }
}
