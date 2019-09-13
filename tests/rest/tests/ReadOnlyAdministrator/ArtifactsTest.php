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

use Test\Rest\Tracker\ArtifactsTestExecutionHelper;

/**
 * @group ArtifactsTest
 */
class ArtifactsTest extends ArtifactsTestExecutionHelper
{
    public function testOptionsArtifactsWithUser(): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->client->options('artifacts')
        );

        $this->assertEquals(
            ['OPTIONS', 'GET', 'POST'],
            $response->getHeader('Allow')->normalize()->toArray()
        );

        $response = $this->getResponse(
            $this->client->options('artifacts/9')
        );

        $this->assertEquals(
            ['OPTIONS', 'GET', 'PUT', 'DELETE', 'PATCH'],
            $response->getHeader('Allow')->normalize()->toArray()
        );
    }

    public function testPostArtifactWithUserNonMember(): void
    {
        $summary_field_label = 'Summary';
        $summary_field_value = "This is a new epic";

        $post_body = $this->buildPOSTBodyContent($summary_field_label, $summary_field_value);

        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->client->post('artifacts', null, $post_body)
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testGetSuspendedProjectArtifactForUser(): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->client->get('artifacts/' . $this->suspended_tracker_artifacts_ids[1])
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetArtifactWithUser(): void
    {
        $response = $this->getResponseForReadOnlyUserAdmin(
            $this->client->get("artifacts/9")
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetArtifactsLinks(): void
    {
        $nature_is_child = '_is_child';
        $nature_empty    = '';
        $artifact_id     = $this->level_one_artifact_ids[1];

        $response_with_read_only_user = $this->getResponseForReadOnlyUserAdmin(
            $this->client->get("artifacts/$artifact_id/links")
        );
        $this->assertLinks($response_with_read_only_user, $nature_is_child, $artifact_id, $nature_empty);
    }
}
