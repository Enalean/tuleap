<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Exporter $exporter;
    private ExecutionDao&MockObject $execution_dao;
    private Config&MockObject $config;
    private Project $project;

    public function setUp(): void
    {
        parent::setUp();

        $this->config        = $this->createMock(Config::class);
        $this->execution_dao = $this->createMock(ExecutionDao::class);
        $this->exporter      = new Exporter($this->config, new XML_RNGValidator(), $this->execution_dao);

        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public function testItExportsTTMConfigurationInXML()
    {
        $this->config->method('getIssueTrackerId')->willReturn(1);
        $this->config->method('getCampaignTrackerId')->willReturn(2);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(3);
        $this->config->method('getTestExecutionTrackerId')->willReturn(4);
        $this->config->method('isConfigNeeded')->willReturn(false);

        $this->execution_dao
            ->expects($this->once())
            ->method('searchByExecutionTrackerId')
            ->willReturn([]);

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertEquals((string) $xml_content->configuration->issues, 'T1');
        $this->assertEquals((string) $xml_content->configuration->campaigns, 'T2');
        $this->assertEquals((string) $xml_content->configuration->definitions, 'T3');
        $this->assertEquals((string) $xml_content->configuration->executions, 'T4');
    }

    public function testItExportsTTMConfigurationInXMLWithoutIssueTracker(): void
    {
        $this->config->method('getIssueTrackerId')->willReturn(null);
        $this->config->method('getCampaignTrackerId')->willReturn(2);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(3);
        $this->config->method('getTestExecutionTrackerId')->willReturn(4);
        $this->config->method('isConfigNeeded')->willReturn(false);

        $this->execution_dao
            ->expects($this->once())
            ->method('searchByExecutionTrackerId')
            ->willReturn([]);

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertEquals((string) $xml_content->configuration->issues, '');
        $this->assertEquals((string) $xml_content->configuration->campaigns, 'T2');
        $this->assertEquals((string) $xml_content->configuration->definitions, 'T3');
        $this->assertEquals((string) $xml_content->configuration->executions, 'T4');
    }

    public function testItDoesNotExportTTMConfigurationInXMLIfATrackerIsNotSet()
    {
        $this->config->method('getIssueTrackerId')->willReturn(1);
        $this->config->method('getCampaignTrackerId')->willReturn(2);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(3);
        $this->config->method('getTestExecutionTrackerId')->willReturn(false);
        $this->config->method('isConfigNeeded')->willReturn(true);

        $this->execution_dao
            ->expects($this->never())
            ->method('searchByExecutionTrackerId');

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertNull($xml_content);
    }

    public function testItExportExistingExecutionsMapping()
    {
        $this->config->method('getIssueTrackerId')->willReturn(1);
        $this->config->method('getCampaignTrackerId')->willReturn(2);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(3);
        $this->config->method('getTestExecutionTrackerId')->willReturn(4);
        $this->config->method('isConfigNeeded')->willReturn(false);

        $this->execution_dao
            ->expects($this->once())
            ->method('searchByExecutionTrackerId')
            ->willReturn([
                ['execution_artifact_id' => 123, 'definition_changeset_id' => 10001],
                ['execution_artifact_id' => 124, 'definition_changeset_id' => 10002],
            ]);

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertEquals('123', (string) $xml_content->executions->execution[0]['execution_artifact_id']);
        $this->assertEquals('CHANGESET_10001', (string) $xml_content->executions->execution[0]['definition_changeset_id']);
        $this->assertEquals('124', (string) $xml_content->executions->execution[1]['execution_artifact_id']);
        $this->assertEquals('CHANGESET_10002', (string) $xml_content->executions->execution[1]['definition_changeset_id']);
    }
}
