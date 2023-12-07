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

use SimpleXMLElement;
use Tuleap\Widget\Event\ConfigureAtXMLImport;

class ProjectDashboardXMLImporterPluginTest extends ProjectDashboardXMLImporterBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->project_dashboard_importer = new ProjectDashboardXMLImporter(
            $this->project_dashboard_saver,
            $this->widget_factory,
            $this->widget_dao,
            $this->logger,
            $this->event_manager,
            $this->disabled_widgets_checker
        );
    }

    public function testItImportsAWidgetDefinedInAPlugin()
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

        $this->widget_dao->shouldReceive('createLine')->andReturns(12);
        $this->widget_dao->shouldReceive('createColumn')->andReturns(122);

        $this->mappings_registry->addReference('K123', 78998);

        $widget = \Mockery::spy(\Widget::class);
        $widget->shouldReceive('getId')->andReturns('kanban');

        $this->event_manager->shouldReceive('processEvent')->with(\Mockery::on(function (ConfigureAtXMLImport $event) {
            $event->setContentId(35);
            $event->setWidgetIsConfigured();
            return true;
        }))->once();

        $this->widget_factory->shouldReceive('getInstanceByWidgetName')->with('plugin_agiledashboard_projects_kanban')->andReturns($widget);

        $this->widget_dao->shouldReceive('insertWidgetInColumnWithRank')->with('kanban', 35, 122, 1)->once();

        $this->disabled_widgets_checker->shouldReceive('isWidgetDisabled')->andReturnFalse();

        $this->project_dashboard_importer->import($xml, $this->user, $this->project, $this->mappings_registry);
    }
}
