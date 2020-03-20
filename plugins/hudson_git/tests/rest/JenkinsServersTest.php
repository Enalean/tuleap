<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Tests\REST;

use RestBase;

class JenkinsServersTest extends RestBase
{
    public const TEST_JENKINS_SERVERS_SHORTNAME = 'test-jenkins-servers';

    public function testGetJenkinsServers()
    {
        $response = $this->getResponseByName(
            \REST_TestDataBuilder::ADMIN_USER_NAME,
            $this->client->get('projects/' . $this->getProjectId(self::TEST_JENKINS_SERVERS_SHORTNAME)   . '/git_jenkins_servers')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $collection = $response->json();
        $servers = $collection['git_jenkins_servers_representations'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, count($servers));
        $this->assertEquals('https://example.com/bar', $servers[1]['url']);
    }
}
