<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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
declare(strict_types=1);

namespace Tuleap\REST;

use Guzzle\Http\Exception\BadResponseException;
use REST_TestDataBuilder;

/**
 * @group ProjectTests
 */
class ProjectServicesTest extends ProjectBase
{
    public function testGETProjectServices()
    {
        $url = 'projects/' . $this->getProjectId(REST_TestDataBuilder::PROJECT_SERVICES) . '/project_services';

        $response = $this->getResponse($this->client->get($url));
        $this->assertEquals($response->getStatusCode(), 200);

        $services = $response->json();

        $expected = [
            'summary'        => ['is_enabled' => true],
            'plugin_tracker' => ['is_enabled' => true],
            'file'           => ['is_enabled' => false],
        ];
        foreach ($services as $key => $service) {
            if (isset($expected[$service['name']])) {
                foreach ($expected[$service['name']] as $property => $value) {
                    $this->assertEquals(
                        $value,
                        $service[$property],
                        "${service['name']} should have $property set to ". var_export($value, true)
                    );
                }
                unset($expected[$service['name']]);
            }
        }
        $this->assertEmpty(
            $expected,
            'Following services not found in response: '. implode(', ', array_keys($expected))
        );
    }
}
