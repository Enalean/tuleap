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

namespace Tuleap\Tracker\Tests\REST\PrivateComment;

use Tuleap\Tracker\REST\DataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PrivateCommentArtifactTest extends TrackerBase
{
    public function testProjectAdminCanSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_PROJECT_ADMIN_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]['last_comment']['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]['last_comment']['body']);
        $this->assertCount(2, $artifact_changesets[1]['last_comment']['ugroups']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['label']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['short_name']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['key']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['label']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['short_name']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['key']);
    }

    public function testTrackerAdminCanSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_TRACKER_ADMIN_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]['last_comment']['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]['last_comment']['body']);
        $this->assertCount(2, $artifact_changesets[1]['last_comment']['ugroups']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['label']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['short_name']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['key']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['label']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['short_name']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['key']);
    }

    public function testSiteAdminCanSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::ADMIN_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]['last_comment']['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]['last_comment']['body']);
        $this->assertCount(2, $artifact_changesets[1]['last_comment']['ugroups']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['label']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['short_name']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['key']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['label']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['short_name']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][1]['key']);
    }

    public function testMembersOfUgroupCanSeeCommentAndOnlyItsUgroup(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_JOHN_SNOW_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]['last_comment']['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]['last_comment']['body']);
        $this->assertCount(1, $artifact_changesets[1]['last_comment']['ugroups']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['label']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['short_name']);
        $this->assertEquals('ugroup_john_snow', $artifact_changesets[1]['last_comment']['ugroups'][0]['key']);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_DAENERYS_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $artifact_changesets);
        $this->assertEquals('', $artifact_changesets[0]['last_comment']['body']);
        $this->assertEquals('Lorem ipsum', $artifact_changesets[1]['last_comment']['body']);
        $this->assertCount(1, $artifact_changesets[1]['last_comment']['ugroups']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][0]['label']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][0]['short_name']);
        $this->assertEquals('ugroup_daenerys', $artifact_changesets[1]['last_comment']['ugroups'][0]['key']);
    }

    public function testMemberNotInUgroupCanNotSeePrivateComment(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_MEMBER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $artifact_changesets);
    }

    public function testProjectAdminCanSeeAllChangesets(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_and_private_field_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_PROJECT_ADMIN_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(7, $artifact_changesets);

        self::assertEquals('', $artifact_changesets[0]['last_comment']['body']);
        $artifact_changesets_values = $artifact_changesets[0]['values'];
        self::assertCount(2, $artifact_changesets_values);
        self::assertEquals('Summary', $artifact_changesets_values[0]['label']);
        self::assertEquals('I submitted this one', $artifact_changesets_values[0]['value']);
        self::assertEquals('Hidden field', $artifact_changesets_values[1]['label']);
        self::assertEquals("I submitted this one too but it's hidden", $artifact_changesets_values[1]['value']);

        self::assertEquals('This comment is shown with change', $artifact_changesets[1]['last_comment']['body']);
        $artifact_changesets_values = $artifact_changesets[1]['values'];
        self::assertCount(2, $artifact_changesets_values);
        self::assertEquals('Summary', $artifact_changesets_values[0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets_values[0]['value']);
        self::assertEquals('Hidden field', $artifact_changesets_values[1]['label']);
        self::assertEquals("I submitted this one too but it's hidden", $artifact_changesets_values[1]['value']);

        self::assertEquals('This comment is not shown to member and its change too', $artifact_changesets[2]['last_comment']['body']);
        $artifact_changesets_values = $artifact_changesets[2]['values'];
        self::assertCount(2, $artifact_changesets_values);
        self::assertEquals('Summary', $artifact_changesets_values[0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets_values[0]['value']);
        self::assertEquals('Hidden field', $artifact_changesets_values[1]['label']);
        self::assertEquals('This is not seen by member', $artifact_changesets_values[1]['value']);

        self::assertEquals('This comment is alone and is not shown', $artifact_changesets[3]['last_comment']['body']);
        $artifact_changesets_values = $artifact_changesets[3]['values'];
        self::assertCount(2, $artifact_changesets_values);
        self::assertEquals('Summary', $artifact_changesets_values[0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets_values[0]['value']);
        self::assertEquals('Hidden field', $artifact_changesets_values[1]['label']);
        self::assertEquals('This is not seen by member', $artifact_changesets_values[1]['value']);

        self::assertEquals('This comment is shown to everybody but not changes for member', $artifact_changesets[4]['last_comment']['body']);
        $artifact_changesets_values = $artifact_changesets[4]['values'];
        self::assertCount(2, $artifact_changesets_values);
        self::assertEquals('Summary', $artifact_changesets_values[0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets_values[0]['value']);
        self::assertEquals('Hidden field', $artifact_changesets_values[1]['label']);
        self::assertEquals('This is updated but not seen by member', $artifact_changesets_values[1]['value']);

        self::assertEquals('This is a public comment alone', $artifact_changesets[5]['last_comment']['body']);
        $artifact_changesets_values = $artifact_changesets[5]['values'];
        self::assertCount(2, $artifact_changesets_values);
        self::assertEquals('Summary', $artifact_changesets_values[0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets_values[0]['value']);
        self::assertEquals('Hidden field', $artifact_changesets_values[1]['label']);
        self::assertEquals('This is updated but not seen by member', $artifact_changesets_values[1]['value']);

        self::assertEquals('There is only a private comment', $artifact_changesets[6]['last_comment']['body']);
        $artifact_changesets_values = $artifact_changesets[6]['values'];
        self::assertCount(2, $artifact_changesets_values);
        self::assertEquals('Summary', $artifact_changesets_values[0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets_values[0]['value']);
        self::assertEquals('Hidden field', $artifact_changesets_values[1]['label']);
        self::assertEquals('This is updated but not seen by member', $artifact_changesets_values[1]['value']);
    }

    /**
     * @return int[]
     */
    public function testProjectMemberCanSeeOnlyChangesetsWithCommentOrChanges(): array
    {
        $changeset_ids = [];
        $response      = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_and_private_field_artifact_id) . '/changesets'),
            DataBuilder::PRIVATE_COMMENT_MEMBER_NAME
        );

        self::assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(4, $artifact_changesets);

        self::assertEquals('', $artifact_changesets[0]['last_comment']['body']);
        self::assertCount(1, $artifact_changesets[0]['values']);
        self::assertEquals('Summary', $artifact_changesets[0]['values'][0]['label']);
        self::assertEquals('I submitted this one', $artifact_changesets[0]['values'][0]['value']);
        $changeset_ids[] = $artifact_changesets[0]['id'];

        self::assertEquals('This comment is shown with change', $artifact_changesets[1]['last_comment']['body']);
        self::assertCount(1, $artifact_changesets[1]['values']);
        self::assertEquals('Summary', $artifact_changesets[1]['values'][0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets[1]['values'][0]['value']);
        $changeset_ids[] = $artifact_changesets[1]['id'];

        self::assertEquals('This comment is shown to everybody but not changes for member', $artifact_changesets[2]['last_comment']['body']);
        self::assertCount(1, $artifact_changesets[2]['values']);
        self::assertEquals('Summary', $artifact_changesets[2]['values'][0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets[2]['values'][0]['value']);
        $changeset_ids[] = $artifact_changesets[2]['id'];

        self::assertEquals('This is a public comment alone', $artifact_changesets[3]['last_comment']['body']);
        self::assertCount(1, $artifact_changesets[3]['values']);
        self::assertEquals('Summary', $artifact_changesets[3]['values'][0]['label']);
        self::assertEquals('I updated this one', $artifact_changesets[3]['values'][0]['value']);
        $changeset_ids[] = $artifact_changesets[3]['id'];

        return $changeset_ids;
    }

    /**
     * @depends testProjectMemberCanSeeOnlyChangesetsWithCommentOrChanges
     * @param int[] $changeset_ids
     */
    public function testReverseOrderMustReturnSameChangesets(array $changeset_ids): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'artifacts/' . urlencode((string) $this->private_comment_and_private_field_artifact_id) . '/changesets?order=desc'),
            DataBuilder::PRIVATE_COMMENT_MEMBER_NAME
        );

        self::assertEquals(200, $response->getStatusCode());

        $artifact_changesets = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(4, $artifact_changesets);

        self::assertEquals($changeset_ids[3], $artifact_changesets[0]['id']);
        self::assertEquals($changeset_ids[2], $artifact_changesets[1]['id']);
        self::assertEquals($changeset_ids[1], $artifact_changesets[2]['id']);
        self::assertEquals($changeset_ids[0], $artifact_changesets[3]['id']);
    }
}
