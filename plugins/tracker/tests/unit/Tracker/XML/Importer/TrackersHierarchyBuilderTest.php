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

namespace Tuleap\Test\Tracker\XML\Importer;

use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Tracker\XML\Importer\TrackersHierarchyBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackersHierarchyBuilderTest extends TestCase
{
    public function testItBuildsTrackersHierarchy(): void
    {
        $tracker            = new SimpleXMLElement(
            '<tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                  </tracker>'
        );
        $hierarchy          = [];
        $expected_hierarchy = [444 => [555]];
        $mapper             = ['T101' => 444, 'T102' => 555];
        $hierarchy          = (new TrackersHierarchyBuilder())->buildTrackersHierarchy($hierarchy, $tracker, $mapper);

        self::assertNotEmpty($hierarchy);
        self::assertNotNull($hierarchy[444]);
        self::assertEquals($hierarchy, $expected_hierarchy);
    }

    public function testItAddsTrackersHierarchyOnExistingHierarchy(): void
    {
        $hierarchy          = [444 => [555]];
        $expected_hierarchy = [444 => [555, 666]];
        $mapper             = ['T101' => 444, 'T103' => 666];
        $xml_tracker        = new SimpleXMLElement(
            '<tracker id="T103" parent_id="T101" instantiate_for_new_projects="1">
                    <name>t30</name>
                    <item_name>t31</item_name>
                    <description>t32</description>
                  </tracker>'
        );

        $hierarchy = (new TrackersHierarchyBuilder())->buildTrackersHierarchy($hierarchy, $xml_tracker, $mapper);

        self::assertNotEmpty($hierarchy);
        self::assertNotNull($hierarchy[444]);
        self::assertEquals($expected_hierarchy, $hierarchy);
    }
}
