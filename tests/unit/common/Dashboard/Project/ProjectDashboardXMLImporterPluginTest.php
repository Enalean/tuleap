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
use Tuleap\Widget\Event\ConfigureAtXMLImport;

final class ProjectDashboardXMLImporterPluginTest extends ProjectDashboardXMLImporterBase
{
    public function testItImportsAWidgetDefinedInAPlugin(): void
    {
        $user = UserTestBuilder::aUser()
            ->withAdministratorOf($this->project)
            ->withoutSiteAdministrator()
            ->build();
        $this->dao->method('searchByProjectIdAndName')->willReturn([]);

        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <project>
              <dashboards>
                <dashboard name="dashboard 1">
                  <line>
                    <column>
                      <widget name="plugin_agiledashboard_projects_kanban">
                        <preference name="kanban">
                            <value name="title">Da kanban</value>
                            <reference name="id" REF="K123"/>
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

        $this->mappings_registry->addReference('K123', 78998);

        $widget = $this->createMock(\Widget::class);
        $widget->method('getId')->willReturn('kanban');
        $widget->method('setOwner');
        $widget->method('isUnique');
        $widget->method('create');

        $this->event_manager->expects(self::once())->method('processEvent')->with(self::callback(function (ConfigureAtXMLImport $event) {
            $event->setContentId(35);
            $event->setWidgetIsConfigured();
            return true;
        }));

        $this->widget_factory->method('getInstanceByWidgetName')->with('plugin_agiledashboard_projects_kanban')->willReturn($widget);

        $this->widget_dao->expects(self::once())->method('insertWidgetInColumnWithRank')->with('kanban', 35, 122, 1);

        $this->disabled_widgets_checker->method('isWidgetDisabled')->willReturn(false);

        $this->project_dashboard_importer->import($xml, $user, $this->project, $this->mappings_registry);
    }
}
