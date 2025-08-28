<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectUserGroupTest extends \Tuleap\REST\RestBase
{
    public function testProjectOwnerCannotBeRemovedFromTheProjectAdministrators(): void
    {
        $project_id             = $this->createProjectAs(RESTTestDataBuilder::ADMIN_USER_NAME);
        $update_admins_response = $this->updateProjectAdmin(
            $project_id,
            RESTTestDataBuilder::ADMIN_USER_NAME,
            [RESTTestDataBuilder::ADMIN_USER_NAME, RESTTestDataBuilder::TEST_USER_1_NAME]
        );
        $this->assertEquals(200, $update_admins_response->getStatusCode());

        $response = $this->updateProjectAdmin(
            $project_id,
            RESTTestDataBuilder::ADMIN_USER_NAME,
            [RESTTestDataBuilder::TEST_USER_1_NAME]
        );
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString('project owner', json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['error']['message']);
    }

    private function createProjectAs(string $user_name): int
    {
        $creation_response = $this->getResponseByName(
            $user_name,
            $this->request_factory->createRequest('POST', 'projects')->withBody($this->stream_factory->createStream(json_encode([
                'shortname'   => 'p' . bin2hex(random_bytes(6)),
                'description' => 'proj_certif Owner Remove Project Admin',
                'label'       => 'proj_certif Owner Remove Project Admin',
                'is_public'   => true,
                'template_id' => $this->project_private_id,
            ])))
        );
        self::assertSame(201, $creation_response->getStatusCode());
        return json_decode($creation_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];
    }

    private function updateProjectAdmin(int $project_id, string $sender, array $project_admins): \Psr\Http\Message\ResponseInterface
    {
        $user_references = [];
        foreach ($project_admins as $project_admin) {
            $user_references[] = ['username' => $project_admin];
        }
        return $this->getResponseByName(
            $sender,
            $this->request_factory->createRequest('PUT', 'user_groups/' . $project_id . '_' . RESTTestDataBuilder::DYNAMIC_UGROUP_PROJECT_ADMINS_ID . '/users')->withBody($this->stream_factory->createStream(json_encode([
                'user_references' => $user_references,
            ])))
        );
    }
}
