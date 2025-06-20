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
use Tuleap\Tracker\Tracker\XML\Importer\XmlTrackersByPriorityOrderer;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XmlTrackersByPriorityOrdererTest extends TestCase
{
    public function testItReturnsEachSimpleXmlTrackerFromTheXmlInput(): void
    {
        $xml = simplexml_load_string((string) file_get_contents(__DIR__ . '/../../../_fixtures/TrackersList.xml'));
        if ($xml === false) {
            throw new \Exception('Unable to load xml file');
        }

        $trackers_result = (new XmlTrackersByPriorityOrderer())->getAllXmlTrackersOrderedByPriority($xml);

        $xml_tracker1 = new SimpleXMLElement(
            '<tracker id="T101" parent_id="0" instantiate_for_new_projects="1">
                    <name>name10</name>
                    <item_name>item11</item_name>
                    <description>desc12</description>
                    <color>inca-silver</color>
                    <cannedResponses />
                  </tracker>'
        );

        $xml_tracker2 = new SimpleXMLElement(
            '<tracker id="T102" parent_id="T101" instantiate_for_new_projects="1">
                    <name>name20</name>
                    <item_name>item21</item_name>
                    <description>desc22</description>
                    <color>inca-silver</color>
                    <cannedResponses />
                  </tracker>'
        );

        $xml_tracker3 = new SimpleXMLElement(
            '<tracker id="T103" parent_id="T102" instantiate_for_new_projects="1">
                    <name>name30</name>
                    <item_name>item31</item_name>
                    <description>desc32</description>
                    <color>inca-silver</color>
                    <cannedResponses />
                  </tracker>'
        );

        $expected_trackers = ['T101' => $xml_tracker1, 'T102' => $xml_tracker2, 'T103' => $xml_tracker3];

        $this->assertCount(3, $trackers_result);
        $this->assertEquals($expected_trackers, $trackers_result);
    }
}
