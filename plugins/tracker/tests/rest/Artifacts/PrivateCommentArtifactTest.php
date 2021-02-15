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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Tests\REST\Artifacts;

use Tuleap\Tracker\REST\DataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

class PrivateCommentArtifactTest extends TrackerBase
{
    public function testProjectAdminCanSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_ADMIN
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]["last_comment"]['body']);
    }

    public function testMemberNotProjectAdminCanNotSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_MEMBER
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('', $artifact_changesets[1]["last_comment"]['body']);
    }
}
