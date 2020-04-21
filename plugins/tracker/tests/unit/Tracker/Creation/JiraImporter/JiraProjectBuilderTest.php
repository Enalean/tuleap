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

namespace Tracker\Creation\JiraImporter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraProjectCollection;

final class JiraProjectBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsRecursivelyProjects(): void
    {
        $project_one = [
            'key'  => "TO",
            'name' => 'toto'
        ];

        $result = [
            'isLast'     => false,
            'maxResults' => 2,
            'startAt'    => 0,
            'values'     => [$project_one]
        ];

        $wrapper = \Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->with('/project/search')->andReturn($result);

        $project_two = [
            'key'  => "TU",
            'name' => 'tutu'
        ];

        $other_result = [
            'isLast'     => true,
            'maxResults' => 2,
            'startAt'    => 1,
            'values'     => [$project_two]
        ];

        $wrapper->shouldReceive('getUrl')->with(
            "/project/search?&startAt=" . urlencode("2") . "&maxResults=" . urlencode("2")
        )->andReturn($other_result);

        $expected_collection = new JiraProjectCollection();
        $expected_collection->addProject(
            [
                'id'    => $project_one['key'],
                'label' => $project_one['name'],
            ]
        );
        $expected_collection->addProject(
            [
                'id'    => $project_two['key'],
                'label' => $project_two['name'],
            ]
        );

        $builder = new JiraProjectBuilder();
        $result  = $builder->build($wrapper);

        $this->assertEquals($expected_collection->getJiraProjects(), $result);
    }

    public function testItThrowsAndExceptionIfRecursiveCallGoesWrong(): void
    {
        $project_one = [
            'key'  => "TO",
            'name' => 'toto'
        ];

        $result = [
            'isLast'     => false,
            'maxResults' => 2,
            'startAt'    => 0,
            'values'     => [$project_one]
        ];

        $wrapper = \Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->with('/project/search')->andReturn($result);

        $wrapper->shouldReceive('getUrl')->with(
            "/project/search?&startAt=" . urlencode("2") . "&maxResults=" . urlencode("2")
        )->andReturn(null);

        $this->expectException(JiraConnectionException::class);
        $this->expectExceptionMessage("can not retrieve full collection");

        $builder = new JiraProjectBuilder();
        $builder->build($wrapper);
    }

    public function itThrowsALogicExceptionIfJiraAPIHaveChanged(): void
    {
        $project_one = [
            'key'      => "TO",
            'dsdsdsds' => 'toto'
        ];

        $result = [
            'isLast'     => false,
            'maxResults' => 2,
            'startAt'    => 0,
            'values'     => [$project_one]
        ];

        $wrapper = \Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->with('/project/search')->andReturn($result);

        $wrapper->shouldReceive('getUrl')->with(
            "/project/search?&startAt=" . urlencode("2") . "&maxResults=" . urlencode("2")
        )->andReturn(null);

        $this->expectException(\LogicException::class);

        $builder = new JiraProjectBuilder();
        $builder->build($wrapper);
    }
}
