<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tests\REST\Artifacts;

use Tuleap\Tracker\REST\DataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

final class UsersArtifactsTest extends TrackerBase
{
    public function testItCannotGetArtifactsFromRandomUser(): void
    {
        $response = $this->getResponse(
            $this->client->get(sprintf('users/120/artifacts?query=%s&offset=0&limit=10', urlencode(json_encode(['assigned_to' => true])))),
            DataBuilder::MY_ARTIFACTS_USER_NAME
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testItFetchesArtifactsSubmittedByUser(): void
    {
        $response = $this->getResponse(
            $this->client->get(sprintf('users/self/artifacts?query=%s&offset=0&limit=10', urlencode(json_encode(['submitted_by' => true])))),
            DataBuilder::MY_ARTIFACTS_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $json = \json_decode($response->getBody(true), true);
        $this->assertCount(2, $json);

        $this->assertEqualsCanonicalizing(
            ['I submitted and I am assigned to this one', 'I submitted this one'],
            $this->getTitles($json)
        );
    }

    public function testItFetchesArtifactsAssignedToUser(): void
    {
        $response = $this->getResponse(
            $this->client->get(sprintf('users/self/artifacts?query=%s&offset=0&limit=10', urlencode(json_encode(['assigned_to' => true])))),
            DataBuilder::MY_ARTIFACTS_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $json = \json_decode($response->getBody(true), true);
        $this->assertCount(2, $json);

        $this->assertEqualsCanonicalizing(
            ['I submitted and I am assigned to this one', 'I am assigned to this one'],
            $this->getTitles($json)
        );
    }

    public function testItFetchesArtifactsAssignedToOrSubmittedByUser(): void
    {
        $response = $this->getResponse(
            $this->client->get(sprintf('users/self/artifacts?query=%s&offset=0&limit=10', urlencode(json_encode(['assigned_to' => true, 'submitted_by' => true])))),
            DataBuilder::MY_ARTIFACTS_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $json = \json_decode($response->getBody(true), true);
        $this->assertCount(3, $json);

        $this->assertEqualsCanonicalizing(
            ['I submitted and I am assigned to this one', 'I am assigned to this one', 'I submitted this one'],
            $this->getTitles($json)
        );
    }

    public function testItFetchesPaginated(): void
    {
        $response = $this->getResponse(
            $this->client->get(sprintf('users/self/artifacts?query=%s&offset=1&limit=1', urlencode(json_encode(['assigned_to' => true, 'submitted_by' => true])))),
            DataBuilder::MY_ARTIFACTS_USER_NAME
        );
        $this->assertEquals(200, $response->getStatusCode());
        $json = \json_decode($response->getBody(true), true);
        $this->assertCount(1, $json);

        $this->assertEqualsCanonicalizing(
            ['I am assigned to this one'],
            $this->getTitles($json)
        );
    }

    private function getTitles(array $json): array
    {
        $titles = [];
        foreach ($json as $artifact) {
            $titles[] = $artifact['title'];
        }
        return $titles;
    }
}
