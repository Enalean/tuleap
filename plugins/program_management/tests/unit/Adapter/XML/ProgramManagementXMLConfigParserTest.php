<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\XML;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramManagementXMLConfigParserTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\TestWith(['__fixtures/none', false])]
    #[\PHPUnit\Framework\Attributes\TestWith(['__fixtures/valid_xml', true])]
    public function testIsThereAConfigToImport(string $xml_dir, bool $is_there_a_config): void
    {
        $extractor = new ProgramManagementXMLConfigParser();

        self::assertEquals($is_there_a_config, $extractor->isThereAConfigToImport(__DIR__ . '/' . $xml_dir));
    }

    public function testItThrowsWhenTheXMLFileIsNotValid(): void
    {
        $this->expectException(\XML_ParseException::class);

        $extractor = new ProgramManagementXMLConfigParser();

        $extractor->parseConfig(__DIR__ . '/__fixtures/invalid_xml');
    }

    public function testItReturnsTheContentOfTheConfigFile(): void
    {
        $extractor = new ProgramManagementXMLConfigParser();

        $xml_config = $extractor->parseConfig(__DIR__ . '/__fixtures/valid_xml');

        self::assertInstanceOf(\SimpleXMLElement::class, $xml_config);
    }
}
