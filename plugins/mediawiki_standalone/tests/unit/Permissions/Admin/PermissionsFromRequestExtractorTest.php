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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PermissionsFromRequestExtractorTest extends TestCase
{
    /**
     * @testWith [{"readers": ["102", "103"], "writers": ["103"], "admins": ["104"]}, ["102", "103"], ["103"], ["104"]]
     *           [{"keys_not_found": []}, [], [], []]
     */
    public function testExtractPermissionsFromRequest(
        array $params,
        array $expected_readers_ugroup_ids,
        array $expected_writers_ugroup_ids,
        array $expected_admins_ugroup_ids,
    ): void {
        $request = (new NullServerRequest())->withParsedBody($params);

        $permissions = PermissionsFromRequestExtractor::extractPermissionsFromRequest($request)->getPermissions();

        self::assertEquals($expected_readers_ugroup_ids, $permissions->readers);
        self::assertEquals($expected_writers_ugroup_ids, $permissions->writers);
        self::assertEquals($expected_admins_ugroup_ids, $permissions->admins);
    }

    /**
     * @testWith [{"readers": "123"}]
     *           [{"readers": [], "writers": "123"}]
     *           [{"readers": [], "writers": [], "admins": "123"}]
     *           ["invalid body"]
     */
    public function testInvalidRequest(mixed $params): void
    {
        $request = (new NullServerRequest())->withParsedBody($params);

        $this->expectException(InvalidRequestException::class);

        PermissionsFromRequestExtractor::extractPermissionsFromRequest($request);
    }
}
