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

use SimpleXMLElement;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectDashboardXMLImporterLinesTest extends ProjectDashboardXMLImporterBase
{
    private \PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserTestBuilder::aUser()
            ->withAdministratorOf($this->project)
            ->withoutSiteAdministrator()
            ->build();
    }

    public function testItImportsALine(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->dao->method('save')->willReturn(10001);
        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectmembers');
        $widget->method('getInstanceId')->willReturn(null);
        $widget->method('setOwner');
        $widget->method('isUnique');
        $widget->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')->with('projectmembers')->willReturn($widget);

        $this->widget_dao->expects(self::once())->method('createLine')->with(10001, ProjectDashboardController::DASHBOARD_TYPE, 1);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsAColumn(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->dao->method('save')->willReturn(10001);

        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectmembers');
        $widget->method('setOwner');
        $widget->method('isUnique');
        $widget->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')->with('projectmembers')->willReturn($widget);

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->expects(self::once())->method('createColumn')->with(12, 1);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItSetOwnerAndIdExplicitlyToOvercomeWidgetDesignedToGatherThoseDataFromHTTP(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturn(122);
        $this->dao->method('save');

        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectmembers');
        $widget->expects(self::once())->method('setOwner')->with(101, ProjectDashboardController::LEGACY_DASHBOARD_TYPE);
        $widget->method('isUnique');
        $widget->method('create');

        $this->widget_factory->method('getInstanceByWidgetName')->with('projectmembers')->willReturn($widget);

        $this->widget_dao->expects(self::once())->method('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItImportsAWidget(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturn(122);
        $this->dao->method('save');
        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectmembers');
        $widget->method('setOwner');
        $widget->method('isUnique');
        $widget->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')->with('projectmembers')->willReturn($widget);

        $this->widget_dao->expects(self::once())->method('insertWidgetInColumnWithRank')->with('projectmembers', 0, 122, 1);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItErrorsWhenWidgetNameIsUnknown(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturn(122);
        $this->dao->method('save');
        $this->widget_factory->method('getInstanceByWidgetName')->willReturn(null);

        $this->widget_dao->expects(self::never())->method('insertWidgetInColumnWithRank');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItErrorsWhenWidgetIsDisabled(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->expects(self::never())->method('createLine');
        $this->widget_dao->expects(self::never())->method('createColumn');
        $this->dao->method('save');
        $widget = $this->createMock(\Widget::class);
        $widget->method('getInstanceId')->willReturn(false);
        $this->widget_factory->method('getInstanceByWidgetName')->willReturn($widget);

        $this->widget_dao->expects(self::never())->method('insertWidgetInColumnWithRank');

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(true);

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItErrorsWhenWidgetContentCannotBeCreated(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->expects(self::never())->method('createLine');
        $this->widget_dao->expects(self::never())->method('createColumn');
        $this->dao->method('save');
        $widget = $this->createMock(\Widget::class);
        $widget->method('getInstanceId')->willReturn(false);
        $widget->method('setOwner');
        $widget->method('isUnique');
        $widget->method('getId');
        $this->widget_factory->method('getInstanceByWidgetName')->willReturn($widget);

        $this->widget_dao->expects(self::never())->method('insertWidgetInColumnWithRank');

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItImportsTwoWidgetsInSameColumn(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturn(122);
        $this->dao->method('save');
        $widget_members = $this->createMock(\Widget::class);
        $widget_members->method('getId')->willReturn('projectmembers');
        $widget_members->method('setOwner');
        $widget_members->method('isUnique');
        $widget_members->method('getId');
        $widget_members->method('create');
        $widget_heartbeat = $this->createMock(\Widget::class);
        $widget_heartbeat->method('getId')->willReturn('projectheartbeat');
        $widget_heartbeat->method('setOwner');
        $widget_heartbeat->method('isUnique');
        $widget_heartbeat->method('getId');
        $widget_heartbeat->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')
            ->withConsecutive(['projectmembers'], ['projectheartbeat'])
            ->willReturnOnConsecutiveCalls($widget_members, $widget_heartbeat);

        $this->widget_dao->expects(self::exactly(2))->method('insertWidgetInColumnWithRank')
            ->withConsecutive(
                ['projectmembers', 0, 122, 1],
                ['projectheartbeat', 0, 122, 2],
            );

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItDoesntImportTwiceUniqueWidgets(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturn(122);
        $this->dao->method('save');
        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectheartbeat');
        $widget->method('isUnique')->willReturn(true);
        $widget->method('setOwner');
        $widget->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')->with('projectheartbeat')->willReturn($widget);

        $this->widget_dao->expects(self::once())->method('insertWidgetInColumnWithRank')->with('projectheartbeat', 0, 122, 1);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasWarningRecords());
    }

    public function testItImportsUniqueWidgetsWhenThereAreInDifferentDashboards(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createColumn')->willReturnOnConsecutiveCalls(122, 222);
        $this->widget_dao->method('createLine');
        $this->dao->method('save');
        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectheartbeat');
        $widget->method('isUnique')->willReturn(true);
        $widget->method('setOwner');
        $widget->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')->with('projectheartbeat')->willReturn($widget);

        $this->widget_dao->expects(self::exactly(2))->method('insertWidgetInColumnWithRank')
            ->withConsecutive(
                ['projectheartbeat', 0, 122, 1],
                ['projectheartbeat', 0, 222, 1],
            );

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertFalse($this->logger->hasWarningRecords());
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItDoesntCreateLineAndColumnWhenWidgetIsNotValid(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->expects(self::never())->method('createLine');
        $this->widget_dao->expects(self::never())->method('createColumn');
        $this->widget_dao->expects(self::never())->method('insertWidgetInColumnWithRank');
        $this->dao->method('save');

        $this->widget_factory->method('getInstanceByWidgetName')->with('projectmembers')->willReturn(null);

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItDoesntImportAPersonalWidget(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->expects(self::never())->method('createLine');
        $this->widget_dao->expects(self::never())->method('createColumn');
        $this->dao->method('save');
        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('myprojects');
        $widget->method('isUnique');
        $widget->method('setOwner');
        $widget->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')->with('myprojects')->willReturn($widget);

        $this->widget_dao->expects(self::never())->method('insertWidgetInColumnWithRank');

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItImportsTwoWidgetsInTwoColumns(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturnOnConsecutiveCalls(122, 124);
        $this->dao->method('save');
        $widget_members = $this->createMock(\Widget::class);
        $widget_members->method('getId')->willReturn('projectmembers');
        $widget_members->method('isUnique');
        $widget_members->method('setOwner');
        $widget_members->method('create');
        $widget_heartbeat = $this->createMock(\Widget::class);
        $widget_heartbeat->method('getId')->willReturn('projectheartbeat');
        $widget_heartbeat->method('isUnique');
        $widget_heartbeat->method('setOwner');
        $widget_heartbeat->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')
            ->withConsecutive(['projectmembers'], ['projectheartbeat'])
            ->willReturnOnConsecutiveCalls($widget_members, $widget_heartbeat);

        $this->widget_dao->expects(self::exactly(2))->method('insertWidgetInColumnWithRank')
            ->withConsecutive(
                ['projectmembers', 0, 122, 1],
                ['projectheartbeat', 0, 124, 1],
            );

        $this->widget_dao->expects(self::once())->method('adjustLayoutAccordinglyToNumberOfWidgets')->with(2, 12);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItImportsTwoWidgetsWithSetLayout(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->dao->method('save')->willReturn(144);

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturnOnConsecutiveCalls(122, 124);
        $widget_members = $this->createMock(\Widget::class);
        $widget_members->method('getId')->willReturn('projectmembers');
        $widget_members->method('isUnique');
        $widget_members->method('setOwner');
        $widget_members->method('create');
        $widget_heartbeat = $this->createMock(\Widget::class);
        $widget_heartbeat->method('getId')->willReturn('projectheartbeat');
        $widget_heartbeat->method('isUnique');
        $widget_heartbeat->method('setOwner');
        $widget_heartbeat->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')
            ->withConsecutive(['projectmembers'], ['projectheartbeat'])
            ->willReturnOnConsecutiveCalls($widget_members, $widget_heartbeat);

        $this->widget_dao->expects(self::exactly(2))->method('insertWidgetInColumnWithRank')
            ->withConsecutive(
                ['projectmembers', 0, 122, 1],
                ['projectheartbeat', 0, 124, 1],
            );

        $this->widget_dao->expects(self::once())->method('updateLayout')->with(12, 'two-columns-small-big');

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItFallsbackToAutomaticLayoutWhenLayoutIsUnknown(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturnOnConsecutiveCalls(122, 124);
        $this->dao->method('save');
        $widget_members = $this->createMock(\Widget::class);
        $widget_members->method('getId')->willReturn('projectmembers');
        $widget_members->method('isUnique');
        $widget_members->method('setOwner');
        $widget_members->method('create');
        $widget_heartbeat = $this->createMock(\Widget::class);
        $widget_heartbeat->method('getId')->willReturn('projectheartbeat');
        $widget_heartbeat->method('isUnique');
        $widget_heartbeat->method('setOwner');
        $widget_heartbeat->method('create');
        $this->widget_factory->method('getInstanceByWidgetName')
            ->withConsecutive(['projectmembers'], ['projectheartbeat'])
            ->willReturnOnConsecutiveCalls($widget_members, $widget_heartbeat);

        $this->widget_dao->expects(self::exactly(2))->method('insertWidgetInColumnWithRank')
            ->withConsecutive(
                ['projectmembers', 0, 122, 1],
                ['projectheartbeat', 0, 124, 1],
            );

        $this->widget_dao->expects(self::never())->method('updateLayout');
        $this->widget_dao->expects(self::once())->method('adjustLayoutAccordinglyToNumberOfWidgets');

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
        self::assertFalse($this->logger->hasErrorRecords());
        self::assertTrue($this->logger->hasWarningRecords());
    }

    public function testItImportsAWidgetWithPreferences(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->method('createLine')->willReturn(12);
        $this->widget_dao->method('createColumn')->willReturn(122);
        $this->dao->method('save');

        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectrss');
        $widget->expects(self::once())->method('create')->with(self::callback(function (\Codendi_Request $request) {
            if (
                $request->get('rss') &&
                $request->getInArray('rss', 'title') === 'Da feed' &&
                $request->getInArray('rss', 'url') === 'https://stuff'
            ) {
                return true;
            }
            return false;
        }))->willReturn(35);
        $widget->method('isUnique');
        $widget->method('setOwner');

        $this->widget_factory->method('getInstanceByWidgetName')->with('projectrss')->willReturn($widget);

        $this->widget_dao->expects(self::once())->method('insertWidgetInColumnWithRank')->with('projectrss', 35, 122, 1);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->event_manager->method('processEvent');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }

    public function testItSkipWidgetCreationWhenCreateRaisesExceptions(): void
    {
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

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

        $this->widget_dao->expects(self::never())->method('createLine');
        $this->widget_dao->expects(self::never())->method('createColumn');
        $this->dao->method('save');

        $this->mappings_registry->addReference('K123', 78998);

        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('projectimageviewer');
        $widget->method('create')->willThrowException(new \Exception("foo"));

        $this->widget_factory->method('getInstanceByWidgetName')->with('projectimageviewer')->willReturn($widget);

        $this->widget_dao->expects(self::never())->method('insertWidgetInColumnWithRank');

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }
}
