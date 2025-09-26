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

use Tuleap\REST\RestBase;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class JenkinsServersTest extends RestBase
{
    public const string TEST_JENKINS_SERVERS_SHORTNAME = 'test-jenkins-servers';

    public function testGetJenkinsServers()
    {
        $response = $this->getResponseByName(
            RESTTestDataBuilder::ADMIN_USER_NAME,
            $this->request_factory->createRequest('GET', 'projects/' . $this->getProjectId(self::TEST_JENKINS_SERVERS_SHORTNAME)   . '/git_jenkins_servers')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $collection = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $servers    = $collection['git_jenkins_servers_representations'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, count($servers));
        $this->assertEquals('https://example.com/bar', $servers[1]['url']);
    }
}
