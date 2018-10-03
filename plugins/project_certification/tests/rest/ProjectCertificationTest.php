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

namespace Tuleap\ProjectCertification\REST;

class ProjectCertificationTest extends \RestBase
{
    public function testOptions()
    {
        $response = $this->getResponse(
            $this->client->options(
                'project_certification/' . \REST_TestDataBuilder::ADMIN_PROJECT_ID
            )
        );

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testProjectOwnerIsNotSetWhenThePluginHasBeenEnabledAfter()
    {
        $response = $this->getResponse(
            $this->client->get('project_certification/' . $this->project_private_id),
            \REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertSame(200, $response->getStatusCode());
        $representation = $response->json();
        $this->assertNull($representation['project_owner']);
    }

    public function testProjectHasAProjectOwnerAtCreation()
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

        $response = $this->getResponse(
            $this->client->get('project_certification/' . $new_project_id),
            \REST_TestDataBuilder::ADMIN_USER_NAME
        );

        $this->assertSame(200, $response->getStatusCode());
        $project_certification_representation = $response->json();
        $this->assertSame(
            $this->user_ids[\REST_TestDataBuilder::ADMIN_USER_NAME],
            $project_certification_representation['project_owner']['id']
        );
    }
}
