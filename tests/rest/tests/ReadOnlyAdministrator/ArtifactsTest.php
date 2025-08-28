<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\REST\ReadOnlyAdministrator;

use Tuleap\REST\ArtifactsTestExecutionHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('ArtifactsTest')]
class ArtifactsTest extends ArtifactsTestExecutionHelper
{
    public function testOptionsArtifactsWithUser(): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('OPTIONS', 'artifacts')
        );

        self::assertEqualsCanonicalizing(
            ['OPTIONS', 'GET', 'POST'],
            explode(', ', $response->getHeaderLine('Allow'))
        );

        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'artifacts/9')
        );

        self::assertEqualsCanonicalizing(
            ['OPTIONS', 'GET', 'PUT', 'DELETE', 'PATCH'],
            explode(', ', $response->getHeaderLine('Allow'))
        );
    }

    public function testPostArtifactWithUserNonMember(): void
    {
        $summary_field_label = 'Summary';
        $summary_field_value = 'This is a new epic';

        $post_body = $this->buildPOSTBodyContent($summary_field_label, $summary_field_value);

        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('POST', 'artifacts')
                ->withBody($this->stream_factory->createStream($post_body))
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGetSuspendedProjectArtifactForUser(): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('GET', 'artifacts/' . $this->suspended_tracker_artifacts_ids[1])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetArtifactWithUser(): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('GET', 'artifacts/9')
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetArtifactsLinks(): void
    {
        $nature_is_child = '_is_child';
        $nature_empty    = '';
        $artifact_id     = $this->level_one_artifact_ids[1];

        $response_with_read_only_user = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id/links")
        );
        $this->assertLinks($response_with_read_only_user, $nature_is_child, $artifact_id, $nature_empty);
    }
}
