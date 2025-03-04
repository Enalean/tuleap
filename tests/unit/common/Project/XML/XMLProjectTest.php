<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Project\XML;

use Tuleap\Dashboard\XML\XMLDashboard;
use Tuleap\Project\Service\XML\XMLService;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLProjectTest extends TestCase
{
    public function testExportBasicInfo(): void
    {
        $project = new XMLProject('lorem', 'Ipsum', 'Doloret', 'public');
        $xml     = $project->export();

        self::assertEquals('lorem', $xml['unix-name']);
        self::assertEquals('Ipsum', $xml['full-name']);
        self::assertEquals('Doloret', $xml['description']);
        self::assertEquals('public', $xml['access']);
        self::assertCount(1, $xml->{'long-description'});
        self::assertEquals('', (string) $xml->{'long-description'});
        self::assertCount(1, $xml->services);
        self::assertCount(0, $xml->services->service);
        self::assertCount(0, $xml->dashboards);
    }

    public function testExportServices(): void
    {
        $project = (new XMLProject('lorem', 'Ipsum', 'Doloret', 'public'))
            ->withService(XMLService::buildEnabled('git'))
            ->withService(XMLService::buildDisabled('docman'));

        $xml = $project->export();

        self::assertCount(2, $xml->services->service);
        self::assertEquals('git', (string) $xml->services->service[0]['shortname']);
        self::assertEquals('docman', (string) $xml->services->service[1]['shortname']);
    }

    public function testExportDashboards(): void
    {
        $project = (new XMLProject('lorem', 'Ipsum', 'Doloret', 'public'))
            ->withDashboard(new XMLDashboard('Team view'))
            ->withDashboard(new XMLDashboard('Manager view'));

        $xml = $project->export();

        self::assertCount(1, $xml->dashboards);
        self::assertCount(2, $xml->dashboards->dashboard);
        self::assertEquals('Team view', (string) $xml->dashboards->dashboard[0]['name']);
        self::assertEquals('Manager view', (string) $xml->dashboards->dashboard[1]['name']);
    }

    public function testExportSpreadDashboards(): void
    {
        $project = (new XMLProject('lorem', 'Ipsum', 'Doloret', 'public'))
            ->withDashboards(
                new XMLDashboard('Team view'),
                new XMLDashboard('Manager view')
            );

        $xml = $project->export();

        self::assertCount(1, $xml->dashboards);
        self::assertCount(2, $xml->dashboards->dashboard);
        self::assertEquals('Team view', (string) $xml->dashboards->dashboard[0]['name']);
        self::assertEquals('Manager view', (string) $xml->dashboards->dashboard[1]['name']);
    }
}
