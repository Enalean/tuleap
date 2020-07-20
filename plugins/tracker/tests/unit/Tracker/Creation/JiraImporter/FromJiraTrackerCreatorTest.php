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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tracker;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use XML_SimpleXMLCDATAFactory;

class FromJiraTrackerCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\XML_SimpleXMLCDATAFactory
     */
    private $xml_cdata_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerCreationDataChecker
     */
    private $creation_data_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerXmlImport
     */
    private $tracker_xml_import;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JiraUserOnTuleapCache
     */
    private $jira_user_on_tuleap_cache;

    protected function setUp(): void
    {
        $this->tracker_xml_import        = Mockery::mock(TrackerXmlImport::class);
        $this->tracker_factory           = Mockery::mock(TrackerFactory::class);
        $this->creation_data_checker     = Mockery::mock(TrackerCreationDataChecker::class);
        $this->xml_cdata_factory         = new XML_SimpleXMLCDATAFactory();
        $this->logger                    = Mockery::mock(LoggerInterface::class);
        $this->jira_user_on_tuleap_cache = Mockery::mock(JiraUserOnTuleapCache::class);
    }

    public function testItDuplicatedATrackerFromJira(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(101);

        $this->creation_data_checker->shouldReceive('checkAtProjectCreation')->once();

        $creator = Mockery::mock(
            FromJiraTrackerCreator::class,
            [
                $this->tracker_xml_import,
                $this->tracker_factory,
                $this->creation_data_checker,
                $this->xml_cdata_factory,
                $this->logger,
                $this->jira_user_on_tuleap_cache
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $jira_exporter = Mockery::mock(JiraXmlExporter::class);
        $creator->shouldReceive('getJiraExporter')->andReturn($jira_exporter);

        $jira_exporter->shouldReceive('exportJiraToXml')->once();
        $this->tracker_xml_import->shouldReceive('import')->once()->andReturn([1]);
        $this->tracker_factory->shouldReceive('getTrackerById')->with(1)->andReturn(Mockery::mock(Tracker::class));

        $this->logger->shouldReceive('info');

        $creator->createFromJira(
            $project,
            'my new tracker',
            'my_tracker',
            'tracker desc',
            'inca-silver',
            new ConcealedString('azerty123'),
            'user@example.com',
            'https://example.com',
            'Jira project',
            'Story',
            Mockery::mock(\PFUser::class)
        );
    }
}
