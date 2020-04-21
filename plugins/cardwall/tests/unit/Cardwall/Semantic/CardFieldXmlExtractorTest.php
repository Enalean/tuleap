<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Cardwall\Semantic;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XML_Security;

class CardFieldXmlExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XML_Security
     */
    private $xml_security;

    public function setUp(): void
    {
        parent::setUp();

        $this->xml_security = new XML_Security();
        $this->xml_security->enableExternalLoadOfEntities();
    }

    public function tearDown(): void
    {
        $this->xml_security->disableExternalLoadOfEntities();

        parent::tearDown();
    }

    public function testItImportsACardFieldsSemanticFromXMLFormat()
    {
        $xml = simplexml_load_file(__DIR__ . '/_fixtures/ImportCardwallSemanticCardFields.xml');

        $mapping   = [
            'F13' => 102,
            'F14' => 103
        ];
        $extractor = new CardFieldXmlExtractor();
        $fields    = $extractor->extractFieldFromXml($xml, $mapping);

        $this->assertTrue(in_array(102, $fields));
        $this->assertTrue(in_array(103, $fields));
    }

    public function testItImportsBackgroundColorSemanticFromXMLFormat()
    {
        $xml = simplexml_load_file(__DIR__ . '/_fixtures/ImportCardwallSemanticCardFields.xml');

        $status = \Mockery::spy('Tracker_FormElement_Field');
        $status->shouldReceive('getId')->andReturn(101);
        $status->shouldReceive('getLabel')->andReturn('status');

        $severity = \Mockery::spy('Tracker_FormElement_Field');
        $severity->shouldReceive('getId')->andReturn(102);
        $severity->shouldReceive('getLabel')->andReturn('severity');

        $mapping   = [
            'F13' => $status,
            'F14' => $severity
        ];
        $extractor = new CardFieldXmlExtractor();
        $background_color_field = $extractor->extractBackgroundColorFromXml($xml, $mapping);

        $this->assertEquals(102, $background_color_field->getId());
    }
}
