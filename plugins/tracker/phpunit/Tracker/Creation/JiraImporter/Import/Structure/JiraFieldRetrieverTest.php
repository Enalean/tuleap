<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;

final class JiraFieldRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExportsJiraFieldAndBuildAnArraySortedById(): void
    {
        $wrapper = \Mockery::mock(ClientWrapper::class);
        $field_retriever = new JiraFieldRetriever($wrapper);

        $field_one['id'] = "10";
        $field_two['id'] = "10004";

        $wrapper->shouldReceive('getUrl')->with('/field')->andReturn([$field_one, $field_two]);

        $expected["10"] = $field_one;
        $expected["10004"] = $field_two;
        $result = $field_retriever->getAllJiraFields();

        $this->assertEquals($expected, $result);
    }

    public function testReturnsAnEmptyArrayWhenNoFieldIsFounf(): void
    {
        $wrapper = \Mockery::mock(ClientWrapper::class);
        $field_retriever = new JiraFieldRetriever($wrapper);

        $wrapper->shouldReceive('getUrl')->with('/field')->andReturn(null);

        $result = $field_retriever->getAllJiraFields();

        $this->assertEquals([], $result);
    }
}
