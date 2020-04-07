<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\Test\rest\Docman;

require_once __DIR__ . '/../../../vendor/autoload.php';

use REST_TestDataBuilder;
use Tuleap\Docman\Test\rest\DocmanDataBuilder;
use Tuleap\Docman\Test\rest\Helper\DocmanTestExecutionHelper;

final class DocmanProjectServiceTest extends DocmanTestExecutionHelper
{
    public function testGetServiceRepresentationAsAdministrator(): void
    {
        $admin_response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $this->project_id) . '/docman_service'),
            REST_TestDataBuilder::ADMIN_USER_NAME
        );
        $this->assertSame(200, $admin_response->getStatusCode());
        $admin_result = $admin_response->json();
        $this->assertNotNull($admin_result['root_item']);
        $this->assertNotNull($admin_result['permissions_for_groups']);
    }

    public function testGetServiceRepresentationAsRegularDocmanUser(): void
    {
        $response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $this->project_id) . '/docman_service'),
            DocmanDataBuilder::DOCMAN_REGULAR_USER_NAME
        );
        $this->assertSame(200, $response->getStatusCode());
        $result = $response->json();
        $this->assertNotNull($result['root_item']);
        $this->assertNull($result['permissions_for_groups']);
    }

    public function testGetServiceRepresentationAsRESTReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->client->get('projects/' . urlencode((string) $this->project_id) . '/docman_service'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertSame(200, $response->getStatusCode());
        $result = $response->json();
        $this->assertNotNull($result['root_item']);
        $this->assertNotNull($result['permissions_for_groups']);
    }
}
