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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

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
                        new Dashboard(1, "Dashboard 1"),
                        new Dashboard(2, "Dashboard 2"),
                    ];
                }
            },
            new NullLogger()
        );

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');

        $exporter->exportDashboards(ProjectTestBuilder::aProject()->build(), $xml);

        self::assertEquals('Dashboard 1', (string) $xml->dashboards->dashboard[0]['name']);
        self::assertEquals('Dashboard 2', (string) $xml->dashboards->dashboard[1]['name']);
    }
}
