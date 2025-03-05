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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Cardwall\XML;

use SimpleXMLElement;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLCardwallTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportsCardwallWithTracker(): void
    {
        $root_xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_tracker = (new XMLCardwallTracker('T154'))
            ->withColumn((new XMLCardwallColumn('My Column')))
            ->withMapping((new XMLCardwallMapping('T789', 'F789')));

        $xml_cardwall = (new XMLCardwall())
            ->withTracker($xml_tracker)
            ->export($root_xml);

        self::assertSame('cardwall', $xml_cardwall->getName());
        self::assertCount(1, $xml_cardwall->trackers->children());

        $tracker = $xml_cardwall->trackers->tracker[0];
        self::assertSame('tracker', $tracker->getName());
        self::assertEquals('T154', $tracker['id']);
        self::assertCount(2, $tracker->children());

        self::assertSame('columns', $tracker->children()[0]->getName());
        self::assertCount(1, $tracker->columns->children());
        self::assertSame('column', $tracker->columns->column[0]->getName());
        self::assertEquals('My Column', $tracker->columns->column[0]['label']);

        self::assertSame('mappings', $tracker->children()[1]->getName());
        self::assertCount(1, $tracker->mappings->children());
        self::assertSame('mapping', $tracker->mappings->mapping[0]->getName());
        self::assertEquals('T789', $tracker->mappings->mapping[0]['tracker_id']);
        self::assertEquals('F789', $tracker->mappings->mapping[0]['field_id']);
    }
}
