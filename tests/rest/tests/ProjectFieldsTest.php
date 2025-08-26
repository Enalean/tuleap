<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\REST\v1;

use REST_TestDataBuilder;
use Tuleap\REST\RestBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectFieldsTest extends RestBase
{
    public function testGETProjectFields(): void
    {
        $expected_result = [
            'project_fields_representation' => [
                [
                    'id'          => 1,
                    'name'        => 'Test Rest',
                    'rank'        => 2,
                    'is_required' => false,
                    'description' => 'Field for test rest',
                    'type'        => 'text',
                ],
            ],
            'total_size'                    => 1,
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'project_fields/'),
            REST_TestDataBuilder::TEST_USER_1_NAME
        );

        $this->assertEquals($expected_result, json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }
}
