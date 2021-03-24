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

class RoadmapResourceTest extends \RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse(
            $this->client->options('roadmaps/1/tasks')
        );
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['OPTIONS', 'GET'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGetTasks(): int
    {
        // The widget id is hardcoded because we do not have any REST route which can provide us the widget id
        $response = $this->getResponse(
            $this->client->get('roadmaps/1/tasks'),
        );

        self::assertEquals(200, $response->getStatusCode());
        $tasks = $response->json();
        self::assertCount(1, $tasks);
        self::assertEquals('My artifact', $tasks[0]['title']);

        return $tasks[0]['id'];
    }
}
