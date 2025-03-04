<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Project\XML;

use Tuleap\NeverThrow\Fault;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLFileContentRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testValidXML(): void
    {
        $retriever = new XMLFileContentRetriever();

        $result = $retriever->getSimpleXMLElementFromString('<?xml version="1.0" encoding="UTF-8" ?><project><tracker /></project>');

        $result->match(
            function (\SimpleXMLElement $xml) {
                self::assertEquals('<tracker/>', $xml->tracker->saveXML());
            },
            function () {
                self::fail('Valid xml should not be err');
            }
        );
    }

    public function testInvalidXML(): void
    {
        $retriever = new XMLFileContentRetriever();

        $result = $retriever->getSimpleXMLElementFromString('<?xml version="1.0" encoding="UTF-8" ?><project>');

        $result->match(
            function () {
                self::fail('Invalid xml should not be ok');
            },
            function (Fault $fault) {
                self::assertStringContainsString('Premature end of data in tag project line 1', (string) $fault);
            }
        );
    }
}
