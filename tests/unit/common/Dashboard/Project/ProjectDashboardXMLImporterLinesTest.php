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

namespace Tuleap\Dashboard\Project;

require_once __DIR__ . '/ProjectDashboardXMLImporterBase.php';

use Psr\Log\LogLevel;
use SimpleXMLElement;

class ProjectDashboardXMLImporterLinesTest extends ProjectDashboardXMLImporterBase
{
    public function testItImportsALine()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->dao->shouldReceive('save')->andReturns(10001);
        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectmembers');
        $widget->shouldReceive('getInstanceId')->andReturns(null);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns($widget);

        $this->widget_dao->shouldReceive('createLine')->with(10001, ProjectDashboardController::DASHBOARD_TYPE, 1)->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsAColumn()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->dao->shouldReceive('save')->andReturns(10001);

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->with(12, 1)->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItSetOwnerAndIdExplicitlyToOvercomeWidgetDesignedToGatherThoseDataFromHTTP()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);

        $widget = \Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock();
        $widget->shouldReceive('setOwner')->with(101, ProjectDashboardController::LEGACY_DASHBOARD_TYPE)->once();

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsAWidget()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItErrorsWhenWidgetNameIsUnknown()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="stuff"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->andReturns(null);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), [])->once();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItErrorsWhenWidgetIsDisabled()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getInstanceId')->andReturns(false)->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), [])->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnTrue();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItErrorsWhenWidgetContentCannotBeCreated()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="stuff"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getInstanceId')->andReturns(false)->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), [])->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsTwoWidgetsInSameColumn()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), \Mockery::any())->never();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 122, 2)->ordered();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItDoesntImportTwiceUniqueWidgets()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);
        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectheartbeat');
        $widget->shouldReceive('isUnique')->andReturns(true);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns($widget);

        $this->logger->shouldReceive('log')->with(LogLevel::WARNING, \Mockery::any(), [])->once();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(1);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 122, 1);

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsUniqueWidgetsWhenThereAreInDifferentDashboards()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
                <dashboard name="dashboard 2">
                  <line>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(222)->ordered();
        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectheartbeat');
        $widget->shouldReceive('isUnique')->andReturns(true);
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns($widget);

        $this->logger->shouldReceive('log')->with(LogLevel::WARNING, \Mockery::any(), \Mockery::any())->never();
        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), \Mockery::any())->never();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 222, 1)->ordered();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItDoesntCreateLineAndColumnWhenWidgetIsNotValid()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="stuff"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(null);

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItDoesntImportAPersonalWidget()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="myprojects"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('myprojects')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('myprojects')->getMock());

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), [])->once();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsTwoWidgetsInTwoColumns()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), \Mockery::any())->never();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(124)->ordered();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 124, 1)->ordered();

        $this->widget_dao->shouldReceive('adjustLayoutAccordinglyToNumberOfWidgets')->with(2, 12)->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsTwoWidgetsWithSetLayout()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line layout="two-columns-small-big">
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->dao->shouldReceive('save')->andReturns(144);

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), \Mockery::any())->never();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(124)->ordered();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 124, 1)->ordered();

        $this->widget_dao->shouldReceive('updateLayout')->with(12, 'two-columns-small-big')->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItFallsbackToAutomaticLayoutWhenLayoutIsUnknown()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line layout="foobar">
                    <column>
                      <widget name="projectmembers"></widget>
                    </column>
                    <column>
                      <widget name="projectheartbeat"></widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->logger->shouldReceive('log')->with(LogLevel::ERROR, \Mockery::any(), \Mockery::any())->never();
        $this->logger->shouldReceive('log')->with(LogLevel::WARNING, \Mockery::any(), [])->once();

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122)->ordered();
        $this->widget_dao->shouldReceive('createColumn')->andReturns(124)->ordered();
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectmembers')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectmembers')->getMock());
        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectheartbeat')->andReturns(\Mockery::spy(\Widget::class)->shouldReceive('getId')->andReturns('projectheartbeat')->getMock());

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->times(2);
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1)->ordered();
        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 124, 1)->ordered();

        $this->widget_dao->shouldReceive('updateLayout')->never();
        $this->widget_dao->shouldReceive('adjustLayoutAccordinglyToNumberOfWidgets')->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsAWidgetWithPreferences()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectrss">
                        <preference name="rss">
                            <value name="title">Da feed</value>
                            <value name="url">https://stuff</value>
                        </preference>
                      </widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);

        $widget = \Mockery::mock(\Widget::class, ['getId' => 'projectrss'])->shouldIgnoreMissing();
        $widget->shouldReceive('create')->with(\Mockery::on(function (\Codendi_Request $request) {
            if (
                $request->get('rss') &&
                $request->getInArray('rss', 'title') === 'Da feed' &&
                $request->getInArray('rss', 'url') === 'https://stuff'
            ) {
                return true;
            }
            return false;
        }))->once()->andReturns(35);

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectrss')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('projectrss', 35, 122, 1)->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItSkipWidgetCreationWhenCreateRaisesExceptions()
    {
        $this->user->shouldReceive('isAdmin')->with(101)->andReturns(true);
        $this->dao->shouldReceive('searchByProjectIdAndName')->andReturns([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="projectimageviewer">
                        <preference name="image">
                            <value name="title">Da kanban</value>
                            <value name="url">https://stuff</value>
                        </preference>
                      </widget>
                    </column>
                  </line>
                </dashboard>
              </dashboards>
              </project>'
        );

        $this->widget_dao->shouldReceive('createLine')->never();
        $this->widget_dao->shouldReceive('createColumn')->never();

        $this->mappings_registry->addReference('K123', 78998);

        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('projectimageviewer');
        $widget->shouldReceive('create')->andThrows(new \Exception("foo"));

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('projectimageviewer')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->never();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }
}
