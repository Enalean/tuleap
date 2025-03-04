<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Cardwall\Semantic;

use SimpleXMLElement;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardFieldXmlExtractorTest extends TestCase
{
    public function testItImportsACardFieldsSemanticFromXMLFormat(): void
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/_fixtures/ImportCardwallSemanticCardFields.xml'),
            SimpleXMLElement::class,
            LIBXML_NONET
        );

        $mapping   = [
            'F13' => 102,
            'F14' => 103,
        ];
        $extractor = new CardFieldXmlExtractor();
        $fields    = $extractor->extractFieldFromXml($xml, $mapping);

        self::assertTrue(in_array(102, $fields));
        self::assertTrue(in_array(103, $fields));
    }

    public function testItImportsBackgroundColorSemanticFromXMLFormat(): void
    {
        $xml = simplexml_load_string(
            file_get_contents(__DIR__ . '/_fixtures/ImportCardwallSemanticCardFields.xml'),
            SimpleXMLElement::class,
            LIBXML_NONET
        );

        $status   = ListFieldBuilder::aListField(101)->withLabel('status')->build();
        $severity = ListFieldBuilder::aListField(102)->withLabel('severity')->build();

        $mapping                = [
            'F13' => $status,
            'F14' => $severity,
        ];
        $extractor              = new CardFieldXmlExtractor();
        $background_color_field = $extractor->extractBackgroundColorFromXml($xml, $mapping);

        self::assertEquals(102, $background_color_field->getId());
    }
}
