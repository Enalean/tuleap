<?php
/**
 * Copyright (c) Enalean, 2017-2018. All rights reserved
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

namespace Tuleap\FRS\Tests\REST\Packages;

use Guzzle\Http\Exception\ClientErrorResponseException;
use REST_TestDataBuilder;
use RestBase;

/**
 * @group FRSTests
 */
class PackagesTest extends RestBase
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
        $response = $this->getResponse($this->client->options('frs_packages'));
        $this->assertEquals(array('OPTIONS', 'POST'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETPackage()
    {
        $response = $this->getResponse($this->client->get('frs_packages/1'));
        $package  = $response->json();

        $this->assertEquals($package['id'], 1);
        $this->assertEquals($package['label'], 'package1');
    }

    public function testPOSTPackages()
    {
        $post_resource = json_encode(array(
            'project_id' => $this->project_id,
            'label' => 'New package'
        ));

        $response = $this->getResponse($this->client->post('frs_packages', null, $post_resource));
        $package  = $response->json();

        $this->assertEquals($package['id'], 2);
        $this->assertEquals($package['label'], 'New package');
    }
}
