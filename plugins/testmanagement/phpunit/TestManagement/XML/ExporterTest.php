<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

require_once __DIR__ . '/../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use XML_RNGValidator;

class ExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Exporter
     */
    private $exporter;
    private $execution_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Config
     */
    private $config;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    public function setUp(): void
    {
        parent::setUp();

        $this->config        = Mockery::mock(Config::class);
        $this->execution_dao = Mockery::mock(ExecutionDao::class);
        $this->exporter      = new Exporter($this->config, new XML_RNGValidator(), $this->execution_dao);

        $this->project = Mockery::spy(Project::class);
    }

    public function testItExportsTTMConfigurationInXML()
    {
        $this->config->shouldReceive([
            'getIssueTrackerId'          => 1,
            'getCampaignTrackerId'       => 2,
            'getTestDefinitionTrackerId' => 3,
            'getTestExecutionTrackerId'  => 4,
            'isConfigNeeded'             => false
        ]);

        $this->execution_dao->shouldReceive('searchByExecutionTrackerId')->once()->andReturn([]);

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertEquals((string) $xml_content->configuration->issues, 'T1');
        $this->assertEquals((string) $xml_content->configuration->campaigns, 'T2');
        $this->assertEquals((string) $xml_content->configuration->definitions, 'T3');
        $this->assertEquals((string) $xml_content->configuration->executions, 'T4');
    }

    public function testItExportsTTMConfigurationInXMLWithoutIssueTracker(): void
    {
        $this->config->shouldReceive([
            'getIssueTrackerId'          => null,
            'getCampaignTrackerId'       => 2,
            'getTestDefinitionTrackerId' => 3,
            'getTestExecutionTrackerId'  => 4,
            'isConfigNeeded'             => false
        ]);

        $this->execution_dao->shouldReceive('searchByExecutionTrackerId')->once()->andReturn([]);

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertEquals((string) $xml_content->configuration->issues, '');
        $this->assertEquals((string) $xml_content->configuration->campaigns, 'T2');
        $this->assertEquals((string) $xml_content->configuration->definitions, 'T3');
        $this->assertEquals((string) $xml_content->configuration->executions, 'T4');
    }

    public function testItDoesNotExportTTMConfigurationInXMLIfATrackerIsNotSet()
    {
        $this->config->shouldReceive([
            'getIssueTrackerId'          => 1,
            'getCampaignTrackerId'       => 2,
            'getTestDefinitionTrackerId' => 3,
            'getTestExecutionTrackerId'  => false,
            'isConfigNeeded'             => true
        ]);

        $this->execution_dao->shouldReceive('searchByExecutionTrackerId')->never();

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertNull($xml_content);
    }

    public function testItExportExistingExecutionsMapping()
    {
        $this->config->shouldReceive([
            'getIssueTrackerId'          => 1,
            'getCampaignTrackerId'       => 2,
            'getTestDefinitionTrackerId' => 3,
            'getTestExecutionTrackerId'  => 4,
            'isConfigNeeded'             => false
        ]);

        $this->execution_dao
            ->shouldReceive('searchByExecutionTrackerId')
            ->once()
            ->andReturn([
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
