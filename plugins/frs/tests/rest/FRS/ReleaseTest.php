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

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use RestBase;

final class ReleaseTest extends RestBase
{
    public const PROJECT_NAME = 'frs-test';

    public function testReleaseIsInPackagesResourcesWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get("frs_packages/1"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertReleaseIsInPackage($response);
    }

    private function assertReleaseIsInPackage(Response $response): void
    {
        $package = $response->json();

        $this->assertEquals(
            [
                'uri' => 'frs_packages/1/frs_release',
            ],
            $package['resources']['releases']
        );
    }

    public function testOPTIONS()
    {
        $response = $this->getResponse($this->client->options('frs_release'));
        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('frs_release'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'POST'], $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSRelease()
    {
        $response = $this->getResponse($this->client->options('frs_release/1'));
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSReleaseWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('frs_release/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETRelease(): void
    {
        $response = $this->getResponse($this->client->get('frs_release/1'));

        $this->assertGETRelease($response);
    }

    public function testGETReleaseWithUserRESTReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('frs_release/1'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETRelease($response);
    }

    private function assertGETRelease(Response $response): void
    {
        $release  = $response->json();

        $this->assertEquals($release['id'], 1);
        $this->assertEquals($release['name'], 'release1');
        $this->assertEquals($release['links'][0]["link"], 'http://example.fr');
        $this->assertEquals($release['links'][0]["release_time"], '2015-12-08T16:55:00+01:00');

        $this->assertCount(1, $release['permissions_for_groups']['can_read']);
        $this->assertEquals('project_members', $release['permissions_for_groups']['can_read'][0]['short_name']);
    }

    public function testGetReleaseWithoutFRSAdminPermissions()
    {
        $response = $this->getResponse($this->client->get('frs_release/1'), REST_TestDataBuilder::TEST_USER_5_NAME);
        $package  = $response->json();

        $this->assertEquals($package['id'], 1);
        $this->assertNull($package['permissions_for_groups']);
    }

    public function testPOSTRelease()
    {
        $response = $this->getResponse($this->client->post('frs_release', null, $this->getPostResource()));
        $release  = $response->json();

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(2, $release['id']);
        $this->assertEquals('Paleo Pumpkin Bread', $release['name']);
        $this->assertEquals('Philophobia', $release['release_note']);
        $this->assertEquals('Food & Dining', $release['changelog']);
        $this->assertEquals('hidden', $release['status']);
    }

    public function testPOSTReleaseWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->post('frs_release', null, $this->getPostResource()),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    private function getPostResource(): string
    {
        return json_encode(array(
            'package_id'   => 1,
            'name'         => 'Paleo Pumpkin Bread',
            'release_note' => 'Philophobia',
            'changelog'    => 'Food & Dining',
            'status'       => 'hidden'
        ));
    }

    public function testPATCHRelease(): void
    {
        $resource_uri = 'frs_release/1';

        $release = $this->getResponse($this->client->get($resource_uri))->json();
        $this->assertEquals($release['name'], 'release1');

        $response = $this->getResponse($this->client->patch($resource_uri, null, $this->getPatchResource()));
        $this->assertEquals(200, $response->getStatusCode());

        $release = $this->getResponse($this->client->get($resource_uri))->json();
        $this->assertEquals($release['name'], 'Release 1.1');
    }

    public function testPATCHReleaseWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->patch('frs_release/1', null, $this->getPatchResource()),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    private function getPatchResource(): string
    {
        return json_encode(array(
            'name' => 'Release 1.1',
        ));
    }
}
