<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\AgileDashboard\Milestone\Backlog\NoRootPlanningException;
use Tuleap\AgileDashboard\Milestone\Backlog\ProvidedAddedIdIsNotInPartOfTopBacklogException;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;

final class XMLImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLImporter
     */
    private $importer;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TopBacklogElementsToAddChecker
     */
    private $top_backlog_elements_to_add_checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UnplannedArtifactsAdder
     */
    private $unplanned_artifacts_adder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao                = Mockery::mock(ExplicitBacklogDao::class);
        $this->top_backlog_elements_to_add_checker = Mockery::mock(TopBacklogElementsToAddChecker::class);
        $this->unplanned_artifacts_adder           = Mockery::mock(UnplannedArtifactsAdder::class);

        $this->importer = new XMLImporter(
            $this->explicit_backlog_dao,
            $this->top_backlog_elements_to_add_checker,
            $this->unplanned_artifacts_adder
        );

        $this->project = Mockery::mock(Project::class)->shouldReceive('getID')->andReturn('101')->getMock();
        $this->user    = Mockery::mock(PFUser::class);

        $this->logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
    }

    public function testItDoesNothingIfAdminNodeIsNotInXML(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><agiledashboard><plannings/></agiledashboard>');

        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsUsingExplicitBacklog');

        $this->importer->importConfiguration($xml, $this->project);
    }

    public function testItDoesNothingIfExplicitBacklogIsFalseInXML(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard>
                <admin>
                    <scrum>
                        <explicit_backlog is_used="false"/>
                    </scrum>
                </admin>
                <plannings/>
            </agiledashboard>
        ');

        $this->explicit_backlog_dao->shouldNotReceive('setProjectIsUsingExplicitBacklog');

        $this->importer->importConfiguration($xml, $this->project);
    }

    public function testItSetsExplicitBacklogInXMLImport(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <agiledashboard>
                <admin>
                    <scrum>
                        <explicit_backlog is_used="1"/>
                    </scrum>
                </admin>
                <plannings/>
            </agiledashboard>
        ');

        $this->explicit_backlog_dao->shouldReceive('setProjectIsUsingExplicitBacklog')->with(101)->once();

        $this->importer->importConfiguration($xml, $this->project);
    }

    public function testItDoesNotImportDataIfTopBacklogNodeNotInXML(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><agiledashboard/>');

        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            $this->logger
        );
    }

    public function testItDoesNotImportDataIfProjectDoesNotUsesExplicitBacklog(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><agiledashboard><top_backlog/></agiledashboard>');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnFalse();

        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds');

        $this->logger->shouldReceive('warning')->once();

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            $this->logger
        );
    }

    public function testItDoesNotImportDataIfProjectDoesNotHaveRootPlanning(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
        <agiledashboard>
            <top_backlog>
                <artifact artifact_id ="125"/>
            </top_backlog>
        </agiledashboard>
        ');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds');

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->once()
            ->andThrow(new NoRootPlanningException());

        $this->logger->shouldReceive('error')->once();

        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add('125', '225');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            $mapping,
            $this->logger
        );
    }

    public function testItSkipsArtifactsNotInMapping(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
        <agiledashboard>
            <top_backlog>
                <artifact artifact_id ="125"/>
                <artifact artifact_id ="126"/>
            </top_backlog>
        </agiledashboard>
        ');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

        $this->unplanned_artifacts_adder->shouldReceive('addArtifactToTopBacklogFromIds')
            ->once()
            ->with(225, 101);

        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds')
            ->with(226, 101);

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers')->once();

        $this->logger->shouldReceive('warning')->once();

        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add('125', '225');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            $mapping,
            $this->logger
        );
    }

    public function testItSkipsArtifactsNotInTopBacklogTrackers(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
        <agiledashboard>
            <top_backlog>
                <artifact artifact_id ="125"/>
                <artifact artifact_id ="126"/>
            </top_backlog>
        </agiledashboard>
        ');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

        $this->unplanned_artifacts_adder->shouldReceive('addArtifactToTopBacklogFromIds')
            ->once()
            ->with(225, 101);

        $this->unplanned_artifacts_adder->shouldNotReceive('addArtifactToTopBacklogFromIds')
            ->with(226, 101);

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->with(
                $this->project,
                $this->user,
                [225, 226]
            )
            ->andThrow(new ProvidedAddedIdIsNotInPartOfTopBacklogException([226]));

        $this->logger->shouldReceive('warning')->once();

        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add('125', '225');
        $mapping->add('126', '226');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            $mapping,
            $this->logger
        );
    }

    public function testItAddsArtifactsInTopBacklog(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
        <agiledashboard>
            <top_backlog>
                <artifact artifact_id ="125"/>
                <artifact artifact_id ="126"/>
            </top_backlog>
        </agiledashboard>
        ');

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->with(101)
            ->andReturnTrue();

        $this->unplanned_artifacts_adder->shouldReceive('addArtifactToTopBacklogFromIds')
            ->once()
            ->with(225, 101);

        $this->unplanned_artifacts_adder->shouldReceive('addArtifactToTopBacklogFromIds')
            ->once()
            ->with(226, 101);

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers');

        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add('125', '225');
        $mapping->add('126', '226');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            $mapping,
            $this->logger
        );
    }
}
