<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Tracker\Tests\REST\PrivateComment;

use Tuleap\Tracker\REST\DataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PrivateCommentReportQueryTest extends TrackerBase
{
    public function testProjectAdminReportSearchFindsArtifactWithPrivateComment(): void
    {
        $response = $this->performExpertQuery(
            DataBuilder::PRIVATE_COMMENT_PROJECT_ADMIN_NAME
        );

        $this->assertArtifactIsFound($response);
    }

    public function testTrackerAdminReportSearchFindsArtifactWithPrivateComment(): void
    {
        $response = $this->performExpertQuery(
            DataBuilder::PRIVATE_COMMENT_TRACKER_ADMIN_NAME
        );

        $this->assertArtifactIsFound($response);
    }

    public function testNonAdminUserReportSearchFindsArtifactWithPrivateCommentWhenMemberOfSelectedUgroup(): void
    {
        $response = $this->performExpertQuery(
            DataBuilder::PRIVATE_COMMENT_JOHN_SNOW_NAME
        );

        $this->assertArtifactIsFound($response);

        $response = $this->performExpertQuery(
            DataBuilder::PRIVATE_COMMENT_DAENERYS_NAME
        );

        $this->assertArtifactIsFound($response);
    }

    public function testNonAdminUserReportSearchDoesNotFindArtifactWithPrivateCommentWhenNotMemberOfSelectedUgroup(): void
    {
        $response = $this->performExpertQuery(
            DataBuilder::PRIVATE_COMMENT_MEMBER_NAME
        );

        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(0, $artifacts);
    }

    private function performExpertQuery(string $user_name)
    {
        $query = urlencode('@comments = "Lorem"');
        $url   = "trackers/$this->private_comment_tracker_id/artifacts?expert_query=$query";

        return $this->getResponse(
            $this->request_factory->createRequest('GET', $url),
            $user_name
        );
    }

    private function assertArtifactIsFound(\Psr\Http\Message\ResponseInterface $response): void
    {
        $artifacts = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $artifacts);
        self::assertSame($this->private_comment_artifact_id, $artifacts[0]['id']);
    }
}
