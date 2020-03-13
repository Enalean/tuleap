<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\REST;

class ProjectOwnershipTest extends \RestBase
{
    public function testOptions()
    {
        $response = $this->getResponse(
            $this->client->options(
                'project_ownership/' . \REST_TestDataBuilder::ADMIN_PROJECT_ID
            )
        );

        $this->assertEquals(array('OPTIONS', 'GET', 'PUT'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testProjectOwnerIsNotSetWhenThePluginHasBeenEnabledAfter()
    {
        $this->assertNull($this->getProjectOwnershipRepresentation($this->project_private_id)['project_owner']);
    }

    public function testProjectHasAProjectOwnerAtCreationAndBeUpdated()
    {
        $creation_response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->post(
                'projects',
                null,
                json_encode([
                    'shortname'   => 'p' . bin2hex(random_bytes(6)),
                    'description' => 'Test Project Certification Owner',
                    'label'       => 'Test Project Certification Owner',
                    'is_public'   => true,
                    'template_id' => $this->project_private_id
                ])
            )
        );
        $this->assertSame(201, $creation_response->getStatusCode());
        $new_project_id = $creation_response->json()['id'];

        $project_ownership_representation = $this->getProjectOwnershipRepresentation($new_project_id);
        $this->assertSame(
            $this->user_ids[\REST_TestDataBuilder::ADMIN_USER_NAME],
            $project_ownership_representation['project_owner']['id']
        );

        $response_update_admins = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                'user_groups/' . $new_project_id . '_' . \REST_TestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID . '/users',
                null,
                json_encode([
                    'user_references' => [
                        ['username' => \REST_TestDataBuilder::ADMIN_USER_NAME],
                        ['username' => \REST_TestDataBuilder::TEST_USER_1_NAME],
                    ]
                ])
            )
        );
        $this->assertSame(200, $response_update_admins->getStatusCode());

        $response_update_project_ownership = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->put(
                'project_ownership/' . $new_project_id,
                null,
                json_encode([
                    'project_owner' => ['username' => \REST_TestDataBuilder::TEST_USER_1_NAME],
                ])
            )
        );
        $this->assertSame(200, $response_update_project_ownership->getStatusCode());

        $updated_project_ownership_representation = $this->getProjectOwnershipRepresentation($new_project_id);
        $this->assertSame(
            $this->user_ids[\REST_TestDataBuilder::TEST_USER_1_NAME],
            $updated_project_ownership_representation['project_owner']['id']
        );
    }

    private function getProjectOwnershipRepresentation(int $project_id): array
    {
        $response = $this->getResponse(
            $this->client->get('project_ownership/' . $project_id),
            \REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertSame(200, $response->getStatusCode());
        return $response->json();
    }
}
