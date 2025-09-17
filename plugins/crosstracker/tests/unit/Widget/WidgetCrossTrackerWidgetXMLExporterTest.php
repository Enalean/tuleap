<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use LogicException;
use Tuleap\CrossTracker\Query\CrossTrackerQueryFactory;
use Tuleap\CrossTracker\Tests\Stub\Query\RetrieveQueriesStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\DB\UUID;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WidgetCrossTrackerWidgetXMLExporterTest extends TestCase
{
    private const string QUERY_ID = '00000000-1b58-7366-ad1b-dfe646c5ce9d';

    private UUID $query_id;

    #[\Override]
    protected function setUp(): void
    {
        $query_id = (new DatabaseUUIDV7Factory())->buildUUIDFromHexadecimalString(self::QUERY_ID)->unwrapOr(null);
        if ($query_id === null) {
            throw new LogicException('UUID factory failed to build from "00000000-1b58-7366-ad1b-dfe646c5ce9d"');
        }
        $this->query_id = $query_id;
    }

    public function testItGeneratesTheXML(): void
    {
        $widget_id = 15;
        $query_1   = [
            'id'          => $this->query_id,
            'query'       => "SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270",
            'title'       => 'i30n',
            'description' => '2.0 L4',
            'widget_id'   => $widget_id,
            'is_default'   => false,
        ];

        $query_2 = [
            'id'          => $this->query_id,
            'query'       => "SELECT @pretty_title FROM @project = 'self' WHERE @status = OPEN()",
            'title'       => 'a110s',
            'description' => '1.8 L4',
            'widget_id'   => 2556,
            'is_default'   => false,
        ];

         $query_3 = [
             'id'          => $this->query_id,
             'query'       => "SELECT @pretty_title FROM @project='self' WHERE power > 200",
             'title'       => 'i20n',
             'description' => '1.6 L4',
             'widget_id'   => $widget_id,
             'is_default'   => true,
         ];

         $exporter = new WidgetCrossTrackerWidgetXMLExporter(
             new CrossTrackerQueryFactory(
                 RetrieveQueriesStub::withQueries($query_1, $query_2, $query_3)
             )
         );

        $expected_xml = new \SimpleXMLElement(
            <<<XML
                <widget name="crosstrackersearch">
                    <preference name="query">
                        <value name="is-default">0</value>
                        <value name="title"><![CDATA[i30n]]></value>
                        <value name="description"><![CDATA[2.0 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @id, @tracker.name, @project.name, @title, @last_update_date, @submitted_by FROM @project = 'self' WHERE power > 270]]></value>
                    </preference>
                    <preference name="query">
                        <value name="is-default">1</value>
                        <value name="title"><![CDATA[i20n]]></value>
                        <value name="description"><![CDATA[1.6 L4]]></value>
                        <value name="tql"><![CDATA[SELECT @pretty_title FROM @project='self' WHERE power > 200]]></value>
                    </preference>
                  </widget>
XML
        );

        $result = $exporter->generateXML($widget_id);
        self::assertTrue($result->isValue());
        $unwrapped_result = $result->unwrapOr(null);
        self::assertInstanceOf(\SimpleXMLElement::class, $unwrapped_result);
        self::assertXmlStringEqualsXmlString((string) $unwrapped_result->asXML(), (string) $expected_xml->asXML());
    }
}
