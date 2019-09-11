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
use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;

/**
 * @group ProjectTests
 */
class ProjectServicesTest extends ProjectBase
{
    public function testGETProjectServices(): void
    {
        $url = "projects/$this->project_services_id/project_services";

        $response = $this->getResponse($this->client->get($url));
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertGETProjectServices($response);
    }

    public function testGETProjectServicesWithRESTReadOnlyUser(): void
    {
        $url = "projects/$this->project_services_id/project_services";

        $response = $this->getResponse(
            $this->client->get($url),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertGETProjectServices($response);
    }

    private function assertGETProjectServices(Response $response): void
    {
        $services = $response->json();

        $expected = [
            'summary' => ['is_enabled' => true],
            'plugin_tracker' => ['is_enabled' => true],
            'file' => ['is_enabled' => false],
        ];
        foreach ($services as $key => $service) {
            if (isset($expected[$service['name']])) {
                foreach ($expected[$service['name']] as $property => $value) {
                    $this->assertEquals(
                        $value,
                        $service[$property],
                        "${service['name']} should have $property set to " . var_export($value, true)
                    );
                }
                unset($expected[$service['name']]);
            }
        }
        $this->assertEmpty(
            $expected,
            'Following services not found in response: ' . implode(', ', array_keys($expected))
        );
    }

    public function testPUTProjectServicesWithRESTReadOnlyUser(): void
    {
        $service = $this->getService('file');

        $is_enabled_value     = $service['is_enabled'];
        $new_is_enabled_value = ! $is_enabled_value;
        $body                 = json_encode(['is_enabled' => $new_is_enabled_value]);

        $response = $this->getResponse(
            $this->client->put($service['uri'], null, $body),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());

        $updated_service = $this->getService('file');
        $this->assertEquals($is_enabled_value, $updated_service['is_enabled']);
    }

    public function testPUTProjectServices(): void
    {
        $service = $this->getService('file');

        $new_is_enabled_value = ! $service['is_enabled'];
        $body                 = json_encode(['is_enabled' => $new_is_enabled_value]);

        $response = $this->getResponse($this->client->put($service['uri'], null, $body));
        $this->assertEquals(200, $response->getStatusCode());

        $updated_service = $this->getService('file');
        $this->assertEquals($new_is_enabled_value, $updated_service['is_enabled']);
    }

    public function testAdminServiceCannotBeDisabled(): void
    {
        $service = $this->getService('admin');

        $body = json_encode(['is_enabled' => false]);

        $response = $this->getResponse($this->client->put($service['uri'], null, $body));
        $this->assertEquals(400, $response->getStatusCode());
    }

    /**
     * @return array
     */
    private function getService(string $name): array
    {
        $url      = "projects/$this->project_services_id/project_services";
        $response = $this->getResponse($this->client->get($url));
        $this->assertEquals(200, $response->getStatusCode());

        $services = $response->json();
        foreach ($services as $service) {
            if ($service['name'] === $name) {
                return $service;
            }
        }

        $this->assertFalse("Cannot find $name service");
    }
}
