<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class RoadmapResourceTest extends \RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'roadmaps/1/tasks')
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['OPTIONS', 'GET'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGetTasks(): void
    {
        // The widget id is hardcoded because we do not have any REST route which can provide us the widget id
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'roadmaps/1/tasks'),
        );

        self::assertEquals(200, $response->getStatusCode());
        $tasks = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(2, $tasks);

        $my_artifact = $tasks[0];
        self::assertEquals('My artifact', $my_artifact['title']);

        $other_artifact = $tasks[1];
        self::assertEquals('Another artifact', $other_artifact['title']);
        self::assertEquals(['' => [$my_artifact['id']]], $other_artifact['dependencies']);

        $subtasks_response = $this->getResponse($this->request_factory->createRequest('GET', $other_artifact['subtasks_uri']));

        self::assertEquals(200, $subtasks_response->getStatusCode());
        self::assertCount(0, json_decode($subtasks_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));
    }
}
