<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Service\XML;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLServiceTest extends TestCase
{
    public function testExportEnabled()
    {
        $service = XMLService::buildEnabled('git');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><services/>');

        $service->export($xml);

        self::assertCount(1, $xml->service);
        self::assertEquals('git', $xml->service['shortname']);
        self::assertEquals('1', $xml->service['enabled']);
    }

    public function testExportDisabled()
    {
        $service = XMLService::buildDisabled('git');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><services/>');

        $service->export($xml);

        self::assertCount(1, $xml->service);
        self::assertEquals('git', $xml->service['shortname']);
        self::assertEquals('0', $xml->service['enabled']);
    }
}
