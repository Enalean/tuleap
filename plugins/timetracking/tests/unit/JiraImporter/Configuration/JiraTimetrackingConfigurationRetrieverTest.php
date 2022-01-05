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
use Psr\Log\NullLogger;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraServerClientStub;

class JiraTimetrackingConfigurationRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private JiraTimetrackingConfigurationRetriever $retriever;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ClientWrapper
     */
    private $jira_client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jira_client = new class extends JiraCloudClientStub {
        };

        $this->retriever = new JiraTimetrackingConfigurationRetriever(
            $this->jira_client,
            new NullLogger()
        );
    }

    public function testItReturnsTheJiraTimetrackingConfigurationName(): void
    {
        $this->jira_client->urls['/rest/api/2/configuration'] = [
            'timeTrackingEnabled'  => true,
        ];

        $configuration = $this->retriever->getJiraTimetrackingConfiguration();

        $this->assertNotNull($configuration);
        $this->assertSame("jira_timetracking", $configuration);
    }

    public function testItReturnsNullIfTheWrapperReturnsFalseForTimeTrackingEnabled(): void
    {
        $this->jira_client->urls['/rest/api/2/configuration'] = [
            'timeTrackingEnabled'  => false,
        ];

        $configuration = $this->retriever->getJiraTimetrackingConfiguration();

        $this->assertNull($configuration);
    }

    public function testItReturnsNullIfKeyEntryIsMissingInJson(): void
    {
        $this->jira_client->urls['/rest/api/2/configuration'] = [];

        $configuration = $this->retriever->getJiraTimetrackingConfiguration();

        $this->assertNull($configuration);
    }

    public function testItReturnsNullIfTimetrackingIsNotFoundOnServer(): void
    {
        $request  = HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/rest/api/2/configuration');
        $response = HTTPFactoryBuilder::responseFactory()->createResponse(404);

        $jira_server_client = new class ($request, $response) extends JiraServerClientStub {
            public function __construct(private $request, private $response)
            {
            }

            public function getUrl(string $url): ?array
            {
                throw JiraConnectionException::responseIsNotOk($this->request, $this->response, null);
            }
        };

        $retriever = new JiraTimetrackingConfigurationRetriever($jira_server_client, new NullLogger());

        $this->assertNull($retriever->getJiraTimetrackingConfiguration());
    }
}
