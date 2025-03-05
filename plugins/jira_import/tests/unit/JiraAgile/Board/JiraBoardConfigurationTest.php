<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\JiraAgile\Board;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraBoardConfigurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAConfigurationWithColumns(): void
    {
        $response = [
            'columnConfig' => [
                'columns' => [
                    [
                        'name'     => 'To Do',
                        'statuses' => [],
                    ],
                    [
                        'name'     => 'Done',
                        'statuses' => [],
                    ],
                ],
            ],
            'estimation'   => [
                'type'  => 'none',
            ],
        ];

        $board_configuration = JiraBoardConfiguration::buildFromAPIResponse($response);

        assertCount(2, $board_configuration->columns);

        assertSame('To Do', $board_configuration->columns[0]->name);
        assertSame('Done', $board_configuration->columns[1]->name);
    }

    public function testItHasEstimationField(): void
    {
        $response = [
            'columnConfig' => [
                'columns' => [
                ],
            ],
            'estimation'   => [
                'type'  => 'field',
                'field' => [
                    'fieldId'     => 'customfield_10014',
                    'displayName' => 'Story Points',
                ],
            ],
        ];

        $board_configuration = JiraBoardConfiguration::buildFromAPIResponse($response);

        assertSame('customfield_10014', $board_configuration->estimation_field);
    }
}
