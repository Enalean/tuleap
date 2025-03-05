<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Project;
use Psr\Log\NullLogger;
use Tuleap\Dashboard\Dashboard;
use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use Tuleap\Dashboard\Widget\IRetrieveDashboardWidgets;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Widget\IBuildInstanceOfWidgets;
use Tuleap\Widget\ProjectMembers\ProjectMembers;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class DashboardXMLExporterTest extends TestCase
{
    public function testItExportsNothingIfNoDashboard(): void
    {
        $exporter = new DashboardXMLExporter(
            new class implements IRetrieveDashboards {
                /**
                 * @return ProjectDashboard[]
                 */
                public function getAllProjectDashboards(Project $project): array
                {
                    return [];
                }
            },
            $this->createMock(IRetrieveDashboardWidgets::class),
            $this->createMock(IBuildInstanceOfWidgets::class),
            new NullLogger()
        );

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');

        $exporter->exportDashboards(ProjectTestBuilder::aProject()->build(), $xml);

        self::assertTrue(empty($xml->dashboards));
    }

    public function testItExportsAllProjectDashboards(): void
    {
        $exporter = new DashboardXMLExporter(
            new class implements IRetrieveDashboards {
                /**
                 * @return ProjectDashboard[]
                 */
                public function getAllProjectDashboards(Project $project): array
                {
                    return [
                        new Dashboard(1, 'Dashboard 1'),
                        new Dashboard(2, 'Dashboard 2'),
                    ];
                }
            },
            new class implements IRetrieveDashboardWidgets {
                /**
                 * @return DashboardWidgetLine[]
                 */
                public function getAllWidgets(int $dashboard_id, string $dashboard_type): array
                {
                    return [];
                }
            },
            $this->createMock(IBuildInstanceOfWidgets::class),
            new NullLogger()
        );

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');

        $exporter->exportDashboards(ProjectTestBuilder::aProject()->build(), $xml);

        self::assertEquals('Dashboard 1', (string) $xml->dashboards->dashboard[0]['name']);
        self::assertEquals('Dashboard 2', (string) $xml->dashboards->dashboard[1]['name']);
    }

    public function testItExportsExportableWidgetsOfADashboard(): void
    {
        $exporter = new DashboardXMLExporter(
            new class implements IRetrieveDashboards {
                /**
                 * @return ProjectDashboard[]
                 */
                public function getAllProjectDashboards(Project $project): array
                {
                    return [
                        new Dashboard(1, 'Dashboard 1'),
                    ];
                }
            },
            new class implements IRetrieveDashboardWidgets {
                /**
                 * @return DashboardWidgetLine[]
                 */
                public function getAllWidgets(int $dashboard_id, string $dashboard_type): array
                {
                    return [
                        new DashboardWidgetLine(
                            2,
                            'two-columns-big-small',
                            [
                                new DashboardWidgetColumn(
                                    3,
                                    1,
                                    [
                                        new DashboardWidget(10, 'projectmembers', 0, 3, 0),
                                    ]
                                ),
                                new DashboardWidgetColumn(
                                    4,
                                    1,
                                    [
                                        new DashboardWidget(11, 'projectdescription', 0, 4, 0),
                                        new DashboardWidget(12, 'unknownwidget', 0, 4, 0),
                                    ]
                                ),
                            ]
                        ),
                        new DashboardWidgetLine(
                            3,
                            'one-column',
                            [
                                new DashboardWidgetColumn(
                                    5,
                                    3,
                                    [
                                        new DashboardWidget(13, 'unknownwidget', 0, 5, 0),
                                    ]
                                ),
                            ]
                        ),
                    ];
                }
            },
            new class implements IBuildInstanceOfWidgets {
                public function getInstanceByWidgetName(string $widget_name): ?\Widget
                {
                    if ($widget_name === 'projectmembers') {
                        return new ProjectMembers();
                    }

                    if ($widget_name === 'projectdescription') {
                        return new \Widget_ProjectDescription();
                    }

                    return null;
                }
            },
            new NullLogger()
        );

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');

        $exporter->exportDashboards(ProjectTestBuilder::aProject()->build(), $xml);

        self::assertCount(1, $xml->dashboards->dashboard[0]->line);
        self::assertEquals('two-columns-big-small', (string) $xml->dashboards->dashboard[0]->line['layout']);
        self::assertCount(2, $xml->dashboards->dashboard[0]->line->column);
        self::assertEquals('projectmembers', (string) $xml->dashboards->dashboard[0]->line->column[0]->widget['name']);
        self::assertEquals('projectdescription', (string) $xml->dashboards->dashboard[0]->line->column[1]->widget['name']);
    }
}
