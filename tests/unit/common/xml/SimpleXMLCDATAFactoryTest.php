<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\XML;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use XML_SimpleXMLCDATAFactory;

class SimpleXMLCDATAFactoryTest extends TestCase
{
    public function testItAddACDATAContentToXML(): void
    {
        $xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');
        $factory = new XML_SimpleXMLCDATAFactory();

        $factory->insert($xml, 'test', 'value01');

        $this->assertEquals('value01', (string) $xml->test);
        $this->assertNull($xml->test['attr1']);
    }

    public function testItAddACDATAContentWithAttributesToXML(): void
    {
        $xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');
        $factory = new XML_SimpleXMLCDATAFactory();

        $factory->insertWithAttributes($xml, 'test', 'value01', ['attr1' => 'valattr1']);

        $this->assertEquals('value01', (string) $xml->test);
        $this->assertEquals('valattr1', (string) $xml->test['attr1']);
    }
}
