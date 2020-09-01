<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\ExplicitBacklog;

use Tuleap\AgileDashboard\REST\TestBase;

require_once __DIR__ . '/../bootstrap.php';

final class MilestonesTest extends TestBase
{
    public function testGetProjectMilestones(): void
    {
        $query = [
            'limit'  => 50,
            'offset' => 0,
            'query'  => json_encode(['status' => 'open']),
            'fields' => 'slim',
            'order'  => 'desc',
        ];

        $uri = 'projects/' . urlencode((string) $this->explicit_backlog_project_id) . '/milestones?' . http_build_query($query);

        $response = $this->getResponse($this->client->get($uri));
        $this->assertEquals(200, $response->getStatusCode());
        $top_milestones = $response->json();

        $this->assertCount(2, $top_milestones);

        $first_open_release = $top_milestones[0];
        $this->assertEquals('Release 02', $first_open_release['label']);
        $second_open_release = $top_milestones[1];
        $this->assertEquals('Release 01', $second_open_release['label']);
    }

    private function getFirstReleaseArtifactId(): int
    {
        return (int) $this->explicit_backlog_artifact_release_ids[1];
    }

    public function testGetMilestoneSubMilestones(): void
    {
        $query = [
            'limit'  => 100,
            'offset' => 0,
            'query'  => json_encode(['status' => 'open']),
            'fields' => 'slim',
            'order'  => 'desc',
        ];

        $uri = 'milestones/' . urlencode((string) $this->getFirstReleaseArtifactId()) . '/milestones?' . http_build_query($query);

        $response = $this->getResponse($this->client->get($uri));
        $this->assertEquals(200, $response->getStatusCode());
        $sub_milestones = $response->json();

        $this->assertCount(2, $sub_milestones);

        $first_open_sprint = $sub_milestones[0];
        $this->assertEquals('Week 37', $first_open_sprint['label']);
        $second_open_sprint = $sub_milestones[1];
        $this->assertEquals('Week 36', $second_open_sprint['label']);
    }
}
