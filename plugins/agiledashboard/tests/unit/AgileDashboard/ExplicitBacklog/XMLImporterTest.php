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

use ColinODell\PsrTestLogger\TestLogger;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use SimpleXMLElement;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\AgileDashboard\Milestone\Backlog\NoRootPlanningException;
use Tuleap\AgileDashboard\Milestone\Backlog\ProvidedAddedIdIsNotInPartOfTopBacklogException;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class XMLImporterTest extends TestCase
{
    private XMLImporter $importer;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private TopBacklogElementsToAddChecker&MockObject $top_backlog_elements_to_add_checker;
    private UnplannedArtifactsAdder&MockObject $unplanned_artifacts_adder;
    private Project $project;
    private PFUser $user;
    private TestLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao                = $this->createMock(ExplicitBacklogDao::class);
        $this->top_backlog_elements_to_add_checker = $this->createMock(TopBacklogElementsToAddChecker::class);
        $this->unplanned_artifacts_adder           = $this->createMock(UnplannedArtifactsAdder::class);

        $this->importer = new XMLImporter(
            $this->explicit_backlog_dao,
            $this->top_backlog_elements_to_add_checker,
            $this->unplanned_artifacts_adder
        );

        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->user    = UserTestBuilder::buildWithDefaults();

        $this->logger = new TestLogger();
    }

    public function testItSetsExplicitBacklogInXMLImportIfAdminNodeIsNotInXML(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><agiledashboard><plannings/></agiledashboard>');

        $this->explicit_backlog_dao->expects(self::once())->method('setProjectIsUsingExplicitBacklog')->with(101);

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

        $this->explicit_backlog_dao->expects(self::never())->method('setProjectIsUsingExplicitBacklog');

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

        $this->explicit_backlog_dao->expects(self::once())->method('setProjectIsUsingExplicitBacklog')->with(101);

        $this->importer->importConfiguration($xml, $this->project);
    }

    public function testItDoesNotImportDataIfTopBacklogNodeNotInXML(): void
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><agiledashboard/>');

        $this->unplanned_artifacts_adder->expects(self::never())->method('addArtifactToTopBacklogFromIds');

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

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->unplanned_artifacts_adder->expects(self::never())->method('addArtifactToTopBacklogFromIds');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            $this->logger
        );

        self::assertTrue($this->logger->hasWarningRecords());
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

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->unplanned_artifacts_adder->expects(self::never())->method('addArtifactToTopBacklogFromIds');

        $this->top_backlog_elements_to_add_checker->expects(self::once())->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->willThrowException(new NoRootPlanningException());

        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add('125', '225');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            $mapping,
            $this->logger
        );

        self::assertTrue($this->logger->hasErrorRecords());
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

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->unplanned_artifacts_adder->expects(self::once())->method('addArtifactToTopBacklogFromIds')
            ->with(225, 101);

        $this->top_backlog_elements_to_add_checker->expects(self::once())->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers');

        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add('125', '225');

        $this->importer->importContent(
            $xml,
            $this->project,
            $this->user,
            $mapping,
            $this->logger
        );

        self::assertTrue($this->logger->hasWarningRecords());
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

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->unplanned_artifacts_adder->expects(self::once())->method('addArtifactToTopBacklogFromIds')
            ->with(225, 101);

        $this->top_backlog_elements_to_add_checker->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->with(
                $this->project,
                $this->user,
                [225, 226]
            )
            ->willThrowException(new ProvidedAddedIdIsNotInPartOfTopBacklogException([226]));

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

        self::assertTrue($this->logger->hasWarningRecords());
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

        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->unplanned_artifacts_adder->expects(self::exactly(2))->method('addArtifactToTopBacklogFromIds')
            ->withConsecutive(
                [225, 101],
                [226, 101],
            );

        $this->top_backlog_elements_to_add_checker->method('checkAddedIdsBelongToTheProjectTopBacklogTrackers');

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
