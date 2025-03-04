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

namespace Tuleap\TestManagement;

use REST_TestDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group TestManagementTest
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DefinitionsTest extends TestManagementRESTTestCase
{
    public function testGetDefinition(): void
    {
        $first_definition = $this->getFirstDefinition(TestManagementDataBuilder::USER_TESTER_NAME);

        $definition_request = $this->request_factory->createRequest('GET', 'testmanagement_definitions/' . $first_definition['id']);
        $definition         = json_decode($this->getResponse($definition_request)->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($definition, $first_definition);
        $this->assertEquals([], $definition['all_requirements']);
    }

    public function testGetDefinitionWithRESTReadOnlyUser(): void
    {
        $first_definition = $this->getFirstDefinition(REST_TestDataBuilder::TEST_BOT_USER_NAME);

        $definition_request = $this->request_factory->createRequest('GET', 'testmanagement_definitions/' . $first_definition['id']);
        $definition         = json_decode($this->getResponse(
            $definition_request,
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        )->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($definition, $first_definition);
    }

    private function getFirstDefinition(string $user_name)
    {
        $campaign  = $this->valid_73_campaign;
        $execution = $this->getFirstExecution($campaign['id'], $user_name);

        return $execution['definition'];
    }

    private function getFirstExecution($campaign_id, string $user_name)
    {
        $executions_request = $this->request_factory->createRequest('GET', 'testmanagement_campaigns/' . $campaign_id . '/testmanagement_executions');
        $executions         = json_decode($this->getResponse(
            $executions_request,
            $user_name
        )->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return $executions[0];
    }
}
