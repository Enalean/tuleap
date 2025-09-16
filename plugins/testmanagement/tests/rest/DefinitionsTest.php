<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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

namespace Tuleap\TestManagement\REST;

use Psl\Json;
use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\TestManagement\REST\Tests\API\TestManagementAPIHelper;
use Tuleap\TestManagement\REST\Tests\TestManagementDataBuilder;
use Tuleap\TestManagement\REST\Tests\TestManagementRESTTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('TestManagementTest')]
final class DefinitionsTest extends TestManagementRESTTestCase
{
    private TestManagementAPIHelper $testmanagement_api;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->testmanagement_api = new TestManagementAPIHelper(
            $this->rest_request,
            $this->request_factory,
        );
    }

    public function testGetDefinition(): void
    {
        $first_definition = $this->getFirstDefinition(TestManagementDataBuilder::USER_TESTER_NAME);

        $definition = $this->testmanagement_api->getTestDefinition(
            $first_definition['id'],
            TestManagementDataBuilder::USER_TESTER_NAME
        );

        self::assertEquals($first_definition, $definition->json);
        self::assertSame([], $definition->getAllRequirements());
    }

    public function testGetDefinitionWithRESTReadOnlyUser(): void
    {
        $first_definition = $this->getFirstDefinition(RESTTestDataBuilder::TEST_BOT_USER_NAME);

        $definition = $this->testmanagement_api->getTestDefinition(
            $first_definition['id'],
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertEquals($first_definition, $definition->json);
    }

    private function getFirstDefinition(string $user_name): array
    {
        $campaign = $this->valid_73_campaign;
        if ($campaign === null) {
            throw new \RuntimeException('Could not find Test Management campaign Tuleap 7.3');
        }
        $execution = $this->getFirstExecution((int) $campaign['id'], $user_name);

        return $execution['definition'];
    }

    private function getFirstExecution(int $campaign_id, string $user_name): array
    {
        $executions_request = $this->request_factory->createRequest(
            'GET',
            'testmanagement_campaigns/' . $campaign_id . '/testmanagement_executions'
        );
        $executions         = Json\decode(
            $this->getResponse($executions_request, $user_name)->getBody()->getContents(),
        );

        return $executions[0];
    }
}
