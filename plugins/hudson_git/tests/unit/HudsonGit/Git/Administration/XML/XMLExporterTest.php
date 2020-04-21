<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration\XML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;

class XMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLExporter
     */
    private $exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jenkins_server_factory = Mockery::mock(JenkinsServerFactory::class);
        $this->logger                 = Mockery::mock(LoggerInterface::class);

        $this->exporter = new XMLExporter(
            $this->jenkins_server_factory,
            $this->logger
        );

        $this->project = Mockery::mock(Project::class);
    }

    public function testItExportsProjectJenkinsServer(): void
    {
        $this->jenkins_server_factory->shouldReceive('getJenkinsServerOfProject')
            ->once()
            ->andReturn([
                new JenkinsServer(1, ('https://url'), $this->project),
                new JenkinsServer(2, ('https://url2'), $this->project),
            ]);

        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git/>
        ');

        $this->logger->shouldReceive('info');

        $this->exporter->export(
            $this->project,
            $xml_git
        );

        $this->assertTrue(isset($xml_git->{"jenkins-servers-admin"}));
        $this->assertCount(2, $xml_git->{"jenkins-servers-admin"}->children());

        $server_01 = $xml_git->{"jenkins-servers-admin"}->{"jenkins-server"}[0];
        $this->assertEquals('https://url', (string) $server_01['url']);

        $server_02 = $xml_git->{"jenkins-servers-admin"}->{"jenkins-server"}[1];
        $this->assertEquals('https://url2', (string) $server_02['url']);
    }

    public function testItDoesNotExportProjectJenkinsServerIfNoServerDefined(): void
    {
        $this->jenkins_server_factory->shouldReceive('getJenkinsServerOfProject')
            ->once()
            ->andReturn([]);

        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git/>
        ');

        $this->exporter->export(
            $this->project,
            $xml_git
        );

        $this->assertFalse(isset($xml_git->{"jenkins-servers-admin"}));
    }
}
