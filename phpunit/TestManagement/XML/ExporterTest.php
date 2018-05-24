<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;
use Tuleap\TestManagement\Config;

class ExporterTest extends TestCase
{
    /**
     * @var Exporter
     */
    private $exporter;

    public function setUp()
    {
        parent::setUp();

        $this->config   = \Mockery::spy(Config::class);
        $this->exporter = new Exporter($this->config, new \XML_RNGValidator());

        $this->project = \Mockery::spy(\Project::class);
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

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertEquals((string) $xml_content->configuration->issues, 'T1');
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

        $xml_content = $this->exporter->exportToXML($this->project);

        $this->assertNull($xml_content);
    }
}
