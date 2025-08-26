<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\LDAP\REST;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LDAPUserGroupTest extends \Tuleap\REST\RestBase
{
    public function testGetLDAPUserGroupRepresentation(): void
    {
        $project_id = $this->getTestProjectID();

        $ugroup_project_members_id = sprintf('%d_3', $project_id);

        $ugroup_response = $this->getResponseForReadOnlyUserAdmin(
            $this->request_factory->createRequest(
                'GET',
                'user_groups/' . urlencode($ugroup_project_members_id)
            )
        );

        self::assertEquals(200, $ugroup_response->getStatusCode());

        $ugroup = json_decode($ugroup_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(
            [
                'dn' => 'cn=mygroup,ou=groups,dc=tuleap,dc=local',
                'label' => 'mygroup',
                'synchro_policy' => 'never',
                'bind_option' => 'preserve_members',
            ],
            $ugroup['additional_information']['ldap']
        );
    }

    private function getTestProjectID(): int
    {
        $projects_response = $this->getResponse(
            $this->request_factory->createRequest(
                'GET',
                'projects?query=' . urlencode(json_encode(['shortname' => 'ldaptests'], JSON_THROW_ON_ERROR))
            )
        );

        self::assertEquals(200, $projects_response->getStatusCode());

        $projects = json_decode($projects_response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(1, $projects);

        return $projects[0]['id'];
    }
}
