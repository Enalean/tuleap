<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

namespace Tuleap\PullRequest;

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\REST\RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('PullRequest')]
class PullRequestsTest extends RestBase
{
    protected function getResponseForNonMember($request)
    {
        return $this->getResponse($request, RESTTestDataBuilder::TEST_USER_2_NAME);
    }

    public function testOPTIONS()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'pull_requests/'));

        $this->assertEquals(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSWithReadOnlyAdmin()
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'pull_requests/'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'POST', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGetPullRequestThrows403IfUserCantSeeGitRepository()
    {
        $response = $this->getResponseForNonMember($this->request_factory->createRequest('GET', 'pull_requests/1'));

        $this->assertEquals($response->getStatusCode(), 403);
    }

    public function testPATCHPullRequestThrow400IfStatusIsUnknown()
    {
        $data = json_encode([
            'status' => 'whatever',
        ]);

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', 'pull_requests/1')->withBody($this->stream_factory->createStream($data)));

        $this->assertEquals(400, $response->getStatusCode());
    }
}
