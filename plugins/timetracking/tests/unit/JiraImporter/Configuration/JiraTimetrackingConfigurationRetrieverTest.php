<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\JiraImporter\Configuration;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;

class JiraTimetrackingConfigurationRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var JiraTimetrackingConfigurationRetriever
     */
    private $retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $jira_client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jira_client = Mockery::mock(ClientWrapper::class);

        $this->retriever = new JiraTimetrackingConfigurationRetriever(
            $this->jira_client,
            new NullLogger()
        );
    }

    public function testItReturnsTheJiraTimetrackingConfigurationName(): void
    {
        $this->jira_client->shouldReceive('getUrl')
            ->once()
            ->with('/rest/api/2/configuration/timetracking')
            ->andReturn([
                'key'  => "JIRA",
                'name' => "JIRA provided time tracking"
            ]);

        $configuration = $this->retriever->getJiraTimetrackingConfiguration();

        $this->assertNotNull($configuration);
        $this->assertSame("jira_timetracking", $configuration);
    }

    public function testItReturnsNullIfTheWrapperReturnsEmptyJson(): void
    {
        $this->jira_client->shouldReceive('getUrl')
            ->once()
            ->with('/rest/api/2/configuration/timetracking')
            ->andReturn([]);

        $configuration = $this->retriever->getJiraTimetrackingConfiguration();

        $this->assertNull($configuration);
    }

    public function testItReturnsNullIfKeyEntryIsMissingInJson(): void
    {
        $this->jira_client->shouldReceive('getUrl')
            ->once()
            ->with('/rest/api/2/configuration/timetracking')
            ->andReturn([
                'name' => "JIRA provided time tracking"
            ]);

        $configuration = $this->retriever->getJiraTimetrackingConfiguration();

        $this->assertNull($configuration);
    }

    public function testItReturnsNullIfKeyIsNotMachingExpectedValueInJson(): void
    {
        $this->jira_client->shouldReceive('getUrl')
            ->once()
            ->with('/rest/api/2/configuration/timetracking')
            ->andReturn([
                'key'  => "whatever",
                'name' => "JIRA provided time tracking"
            ]);

        $configuration = $this->retriever->getJiraTimetrackingConfiguration();

        $this->assertNull($configuration);
    }
}
