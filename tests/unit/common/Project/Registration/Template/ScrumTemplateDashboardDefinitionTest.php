<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template;

use org\bovigo\vfs\vfsStream;
use Tuleap\Dashboard\XML\XMLDashboard;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ScrumTemplateDashboardDefinitionTest extends TestCase
{
    private const PROJECT_XML = __DIR__ . '/../../../../../../tools/setup_templates/scrum/project.xml';

    public function testItKeepsOriginalDashboardByDefault(): void
    {
        $target = vfsStream::setup('tmp')->url() . '/project.xml';

        $dispatcher = EventDispatcherStub::withIdentityCallback();

        $definition = new ScrumTemplateDashboardDefinition($dispatcher);

        $definition->overwriteProjectDashboards(self::PROJECT_XML, $target);

        self::assertXmlFileEqualsXmlFile(self::PROJECT_XML, $target);
        $xml = simplexml_load_string(\Psl\File\read($target));
        self::assertNotFalse($xml);
        self::assertCount(3, $xml->dashboards->dashboard);
    }

    public function testItEnforceAUniqueDashboard(): void
    {
        $target = vfsStream::setup('tmp')->url() . '/project.xml';

        $dashboard_name = 'Supercalifragilisticexpialidocious';

        $dispatcher = EventDispatcherStub::withCallback(
            static function (ScrumTemplateDashboardDefinition $definition) use ($dashboard_name) {
                $definition->enforceUniqueDashboard((new XMLDashboard($dashboard_name)));

                return $definition;
            }
        );

        $definition = new ScrumTemplateDashboardDefinition($dispatcher);

        $definition->overwriteProjectDashboards(self::PROJECT_XML, $target);

        self::assertXmlFileNotEqualsXmlFile(self::PROJECT_XML, $target);
        $xml = simplexml_load_string(\Psl\File\read($target));
        self::assertNotFalse($xml);
        self::assertCount(1, $xml->dashboards->dashboard);
        self::assertNotNull($xml->dashboards->dashboard[0]);
        self::assertSame($dashboard_name, (string) $xml->dashboards->dashboard[0]->attributes()['name']);
    }
}
