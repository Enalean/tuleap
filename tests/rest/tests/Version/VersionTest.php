<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\BuildVersion\REST\v1;

use RestBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class VersionTest extends RestBase
{
    public function testVersionOptions(): void
    {
        $response = $this->getResponseWithoutAuth($this->request_factory->createRequest('OPTIONS', 'version'));

        self::assertEqualsCanonicalizing(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testItReturnsTheTuleapVersion(): void
    {
        $expected_version_number = \trim(\file_get_contents(__DIR__ . '/../../../../VERSION'));

        $response = $this->getResponseWithoutAuth($this->request_factory->createRequest('GET', 'version'));
        self::assertEquals(200, $response->getStatusCode());

        $version = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected_version_number, $version['version_number']);
        self::assertMatchesRegularExpression('/Tuleap \w+ Edition/', $version['flavor_name']);
    }
}
