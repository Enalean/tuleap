<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\xml;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class XMLDateHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExtractFromNodeRaisesExceptionIfFormatIsNotISO8601(): void
    {
        $this->expectException(InvalidDateException::class);

        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <date format="timestamp">1234567890</date>
            EOS
        );

        XMLDateHelper::extractFromNode($xml);
    }

    public function testExtractFromNodeRaisesExceptionIfDateIsNotValid(): void
    {
        $this->expectException(InvalidDateException::class);

        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <date format="ISO8601">1234567890</date>
            EOS
        );

        XMLDateHelper::extractFromNode($xml);
    }

    public function testExtractFromNodeReturnDateTime(): void
    {
        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <date format="ISO8601">2009-02-14T00:31:30+01:00</date>
            EOS
        );

        $this->assertEquals(
            (new \DateTimeImmutable())->setTimestamp(1234567890),
            XMLDateHelper::extractFromNode($xml)
        );
    }

    public function testAddChild(): void
    {
        $xml = new \SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <container/>
            EOS
        );

        $date = (new \DateTimeImmutable())->setTimestamp(1234567890);
        XMLDateHelper::addChild($xml, 'myDate', $date);

        $this->assertEquals('ISO8601', (string) $xml->myDate['format']);
        $this->assertEquals('2009-02-14T00:31:30+01:00', (string) $xml->myDate);
    }
}
