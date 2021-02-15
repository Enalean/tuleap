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
            DataBuilder::PRIVATE_COMMENT_PROJECT_ADMIN_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]["last_comment"]['body']);
        $this->assertCount(2, $artifact_changesets[1]["last_comment"]['ugroups']);
        $this->assertEquals(
            [
                'id' => '115',
                'uri' => 'user_groups/115',
                'label' => 'ugroup_john_snow',
                'users_uri' => 'user_groups/115/users',
                'short_name' => 'ugroup_john_snow',
                'key' => 'ugroup_john_snow',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][0]
        );
        $this->assertEquals(
            [
                'id' => '116',
                'uri' => 'user_groups/116',
                'label' => 'ugroup_daenerys',
                'users_uri' => 'user_groups/116/users',
                'short_name' => 'ugroup_daenerys',
                'key' => 'ugroup_daenerys',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][1]
        );
    }

    public function testTrackerAdminCanSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_TRACKER_ADMIN_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]["last_comment"]['body']);
        $this->assertCount(2, $artifact_changesets[1]["last_comment"]['ugroups']);
        $this->assertEquals(
            [
                'id' => '115',
                'uri' => 'user_groups/115',
                'label' => 'ugroup_john_snow',
                'users_uri' => 'user_groups/115/users',
                'short_name' => 'ugroup_john_snow',
                'key' => 'ugroup_john_snow',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][0]
        );
        $this->assertEquals(
            [
                'id' => '116',
                'uri' => 'user_groups/116',
                'label' => 'ugroup_daenerys',
                'users_uri' => 'user_groups/116/users',
                'short_name' => 'ugroup_daenerys',
                'key' => 'ugroup_daenerys',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][1]
        );
    }

    public function testSiteAdminCanSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]["last_comment"]['body']);
        $this->assertCount(2, $artifact_changesets[1]["last_comment"]['ugroups']);
        $this->assertEquals(
            [
                'id' => '115',
                'uri' => 'user_groups/115',
                'label' => 'ugroup_john_snow',
                'users_uri' => 'user_groups/115/users',
                'short_name' => 'ugroup_john_snow',
                'key' => 'ugroup_john_snow',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][0]
        );
        $this->assertEquals(
            [
                'id' => '116',
                'uri' => 'user_groups/116',
                'label' => 'ugroup_daenerys',
                'users_uri' => 'user_groups/116/users',
                'short_name' => 'ugroup_daenerys',
                'key' => 'ugroup_daenerys',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][1]
        );
    }

    public function testMembersOfUgroupCanSeeCommentAndOnlyItsUgroup(): void
    {
        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_JOHN_SNOW_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]["last_comment"]['body']);
        $this->assertCount(1, $artifact_changesets[1]["last_comment"]['ugroups']);
        $this->assertEquals(
            [
                'id' => '115',
                'uri' => 'user_groups/115',
                'label' => 'ugroup_john_snow',
                'users_uri' => 'user_groups/115/users',
                'short_name' => 'ugroup_john_snow',
                'key' => 'ugroup_john_snow',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][0]
        );

        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_DAENERYS_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]["last_comment"]['body']);
        $this->assertCount(1, $artifact_changesets[1]["last_comment"]['ugroups']);
        $this->assertEquals(
            [
                'id' => '116',
                'uri' => 'user_groups/116',
                'label' => 'ugroup_daenerys',
                'users_uri' => 'user_groups/116/users',
                'short_name' => 'ugroup_daenerys',
                'key' => 'ugroup_daenerys',
            ],
            $artifact_changesets[1]["last_comment"]['ugroups'][0]
        );
    }

    public function testMemberInNoUgroupCanNotSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->client->get('artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_MEMBER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = $response->json();

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]["last_comment"]['body']);
        $this->assertEquals('', $artifact_changesets[1]["last_comment"]['body']);
        $this->assertNull($artifact_changesets[1]["last_comment"]['ugroups']);
    }
}
