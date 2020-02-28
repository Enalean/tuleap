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
use Tuleap\HudsonGit\Git\Administration\JenkinsServerAdder;

class XMLImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLImporter
     */
    private $importer;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerAdder
     */
    private $jenkins_server_adder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jenkins_server_adder = Mockery::mock(JenkinsServerAdder::class);
        $this->logger               = Mockery::mock(LoggerInterface::class);

        $this->importer = new XMLImporter(
            $this->jenkins_server_adder,
            $this->logger
        );

        $this->project = Mockery::mock(Project::class);
    }

    public function testItImportsProjectJenkinsServer(): void
    {
        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git>
                <jenkins-servers-admin>
                    <jenkins-server url="https://example.com/jenkins"/>
                    <jenkins-server url="https://example.com/jenkins2"/>
                </jenkins-servers-admin>
            </git>
        ');

        $this->jenkins_server_adder->shouldReceive('addServerInProject')->times(2);

        $this->logger->shouldReceive('info');

        $this->importer->import(
            $this->project,
            $xml_git
        );
    }

    public function testItDoesNothingIfJenkinsServerNotProvided(): void
    {
        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git/>
        ');

        $this->jenkins_server_adder->shouldReceive('addServerInProject')->never();

        $this->importer->import(
            $this->project,
            $xml_git
        );
    }
}
