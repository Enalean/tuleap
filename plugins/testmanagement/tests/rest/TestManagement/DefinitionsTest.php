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
use TestManagementDataBuilder;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group TestManagementTest
 */
final class DefinitionsTest extends BaseTest
{

    public function testGetDefinition(): void
    {
        $first_definition = $this->getFirstDefinition(TestManagementDataBuilder::USER_TESTER_NAME);

        $definition_request = $this->client->get('testmanagement_definitions/' . $first_definition['id']);
        $definition         = $this->getResponse($definition_request)->json();

        $this->assertEquals($definition, $first_definition);
    }

    public function testGetDefinitionWithRESTReadOnlyUser(): void
    {
        $first_definition = $this->getFirstDefinition(REST_TestDataBuilder::TEST_BOT_USER_NAME);

        $definition_request = $this->client->get('testmanagement_definitions/' . $first_definition['id']);
        $definition         = $this->getResponse(
            $definition_request,
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        )->json();

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
        $executions_request = $this->client->get('testmanagement_campaigns/' . $campaign_id . '/testmanagement_executions');
        $executions         = $this->getResponse(
            $executions_request,
            $user_name
        )->json();

        return $executions[0];
    }
}
