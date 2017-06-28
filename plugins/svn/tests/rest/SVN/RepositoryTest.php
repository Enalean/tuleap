<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

namespace Tuleap\SVN\REST;

use REST_TestDataBuilder;
use RestBase;

class RepositoryTest extends TestBase
{
    protected function getResponse($request)
    {
        return $this->getResponseByToken(
            $this->getTokenForUserName(REST_TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }

    public function testGETRepository()
    {
        $response  = $this->getResponse($this->client->get(
            'svn/1'
        ));

        $repository = $response->json();

        $this->assertArrayHasKey('id', $repository);
        $this->assertEquals($repository['name'], 'repo01');
    }

    public function testOPTIONS()
    {
        $response = $this->getResponse($this->client->options('svn/1'));

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }
}
