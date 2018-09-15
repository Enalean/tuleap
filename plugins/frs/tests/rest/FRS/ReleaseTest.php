<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\FRS\Tests\REST;

use REST_TestDataBuilder;
use RestBase;

class ReleaseTest extends RestBase
{
    const PROJECT_NAME = 'frs-test';

    private $project_id;

    public function setUp()
    {
        parent::setUp();
        $this->project_id = $this->getProjectId(self::PROJECT_NAME);
    }

    public function testOPTIONS()
    {
        $response = $this->getResponse($this->client->options('frs_release'));
        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSRelease()
    {
        $response = $this->getResponse($this->client->options('frs_release/1'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETRelease()
    {
        $response = $this->getResponse($this->client->get('frs_release/1'));
        $release  = $response->json();

        $this->assertEquals($release['id'], 1);
        $this->assertEquals($release['name'], 'release1');
        $this->assertEquals($release['links'][0]["link"], 'http://example.fr');
        $this->assertEquals($release['links'][0]["release_time"], '2015-12-08T16:55:00+01:00');
    }

    public function testPOSTRelease()
    {
        $post_resource = json_encode(array(
            'package_id'   => 1,
            'name'         => 'Paleo Pumpkin Bread',
            'release_note' => 'Philophobia',
            'changelog'    => 'Food & Dining',
            'status'       => 'hidden'
        ));

        $response = $this->getResponse($this->client->post('frs_release', null, $post_resource));
        $release  = $response->json();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(2, $release['id']);
        $this->assertEquals('Paleo Pumpkin Bread', $release['name']);
        $this->assertEquals('Philophobia', $release['release_note']);
        $this->assertEquals('Food & Dining', $release['changelog']);
        $this->assertEquals('hidden', $release['status']);
    }

    public function testPATCHRelease()
    {
        $resource_uri = 'frs_release/1';

        $release = $this->getResponse($this->client->get($resource_uri))->json();
        $this->assertEquals($release['name'], 'release1');

        $patch_resource = json_encode(array(
            'name' => 'Release 1.1',
        ));
        $response = $this->getResponse($this->client->patch($resource_uri, null, $patch_resource));
        $this->assertEquals(200, $response->getStatusCode());

        $release = $this->getResponse($this->client->get($resource_uri))->json();
        $this->assertEquals($release['name'], 'Release 1.1');
    }
}
