<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Action_CopyArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    /**
     * @var SimpleXMLElement
     */
    private $default_xml;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker */
    private $tracker;

    /** @var Tracker_Action_CopyArtifact */
    private $action;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Codendi_Request */
    private $request;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_XML_Exporter_ArtifactXMLExporter */
    private $xml_exporter;

    /** @var int */
    private $changeset_id = 101;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact */
    private $from_artifact;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact */
    private $new_artifact;

    /** @var int */
    private $artifact_id = 123;

    /** @var int */
    private $new_artifact_id = 456;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_IDisplayTrackerLayout */
    private $layout;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact_XMLImport */
    private $xml_importer;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_XML_Updater_ChangesetXMLUpdater */
    private $xml_updater;

    /** @var array */
    private $submitted_values;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact_Changeset */
    private $from_changeset;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_XML_Updater_TemporaryFileXMLUpdater */
    private $file_updater;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_XML_Exporter_ChildrenXMLExporter */
    private $children_xml_exporter;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_XML_Importer_ArtifactImportedMapping */
    private $artifacts_imported_mapping;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_XML_Importer_CopyArtifactInformationsAggregator */
    private $logger;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact */
    private $a_mocked_artifact;

    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory */
    private $tracker_factory;
    /**
     * @var EventManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $changeset_factory = Mockery::spy(Tracker_Artifact_ChangesetFactory::class);
        $this->tracker     = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getId')->andReturn(1);
        $this->tracker->shouldReceive('getItemName');
        $this->tracker->shouldReceive('getProject')->andReturn(
            \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build()
        );
        $this->new_artifact = Mockery::mock(Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $this->new_artifact->setId($this->new_artifact_id);
        $this->layout         = Mockery::spy(Tracker_IDisplayTrackerLayout::class);
        $this->user           = Mockery::spy(PFUser::class);
        $this->xml_exporter   = Mockery::spy(Tracker_XML_Exporter_ArtifactXMLExporter::class);
        $this->xml_importer   = Mockery::spy(Tracker_Artifact_XMLImport::class);
        $this->xml_updater    = Mockery::spy(Tracker_XML_Updater_ChangesetXMLUpdater::class);
        $this->file_updater   = Mockery::spy(Tracker_XML_Updater_TemporaryFileXMLUpdater::class);
        $this->from_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->from_changeset->shouldReceive('getId')->andReturn($this->changeset_id);
        $this->from_artifact = Mockery::mock(Artifact::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $this->from_artifact->setId($this->artifact_id);
        $this->from_artifact->setTracker($this->tracker);
        $this->from_artifact->setChangesets([$this->changeset_id => $this->from_changeset]);
        $this->from_artifact->shouldReceive('getChangesetFactory')->andReturn($changeset_factory);
        $this->from_changeset->shouldReceive('getArtifact')->andReturn($this->from_artifact);
        $this->from_changeset->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->children_xml_exporter      = Mockery::spy(Tracker_XML_Exporter_ChildrenXMLExporter::class);
        $this->artifacts_imported_mapping = Mockery::spy(Tracker_XML_Importer_ArtifactImportedMapping::class);

        $backend_logger = Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->logger   = new Tracker_XML_Importer_CopyArtifactInformationsAggregator($backend_logger);

        $this->submitted_values = [];

        $this->artifact_factory = Mockery::spy(Tracker_ArtifactFactory::class);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->withArgs([$this->user, $this->artifact_id])
            ->andReturn($this->from_artifact);

        $this->request = Mockery::mock(Codendi_Request::class);
        $this->request->shouldReceive('get')->with('from_artifact_id')->andReturn($this->artifact_id);
        $this->request->shouldReceive('get')->with('from_changeset_id')->andReturn($this->changeset_id);
        $this->request->shouldReceive('get')->with('artifact')->andReturn($this->submitted_values);

        $this->tracker_factory = Mockery::spy(\TrackerFactory::class);
        $this->event_manager   = $this->createMock(EventManager::class);

        $this->action = new Tracker_Action_CopyArtifact(
            $this->tracker,
            $this->artifact_factory,
            $this->xml_exporter,
            $this->xml_importer,
            $this->xml_updater,
            $this->file_updater,
            $this->children_xml_exporter,
            $this->artifacts_imported_mapping,
            $this->logger,
            $this->tracker_factory,
            $this->event_manager,
        );

        $this->default_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifacts />');

        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->andReturns($this->default_xml)->byDefault(
        );

        $this->a_mocked_artifact = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->xml_importer->shouldReceive('createFieldsDataBuilder')
            ->andReturn(Mockery::spy(Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder::class));
    }

    public function testItExportsTheRequiredSnapshotArtifact(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);
        $this->xml_importer->shouldReceive('importBareArtifact')->andReturn(Mockery::spy(
            \Tuleap\Tracker\Artifact\Artifact::class
        ));
        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')
            ->withArgs([Mockery::any(), $this->from_changeset])
            ->once()
            ->andReturn($this->default_xml);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItRedirectsToTheNewArtifact(): void
    {
        $xml_content = <<<XML
            <artifacts>
                <artifact id="456" tracker_id="1">
                    <changeset>
                        <submitted_by format="id">101</submitted_by>
                        <submitted_on format="ISO8601">2016-02-15T10:34:38+00:00</submitted_on>
                    </changeset>
                </artifact>
            </artifacts>
XML;
        $xml         = new SimpleXMLElement($xml_content);
        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->once()->andReturns($xml);
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);
        $this->tracker->shouldReceive('getWorkflow')->andReturn(Mockery::spy(\Workflow::class));
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($this->tracker);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);
        $this->xml_importer->shouldReceive('importBareArtifact')->andReturn($this->new_artifact);

        $this->new_artifact->shouldReceive('createNewChangesetWithoutRequiredValidation')->once();

        $this->event_manager->expects(self::once())->method('dispatch');

        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(TRACKER_BASE_URL . '/?aid=456');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNothingAndRedirectsToTheTrackerIfCannotSubmit(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(false);

        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->never();

        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItRedirectsToTheTrackerIfXMLImportFailed(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItUpdatesTheXMLWithIncomingValues(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $this->xml_updater->shouldReceive('update')->withArgs(
            [
                $this->tracker,
                Mockery::any(),
                $this->submitted_values,
                $this->user,
                $_SERVER['REQUEST_TIME'],
            ]
        )->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItUpdateTheXMLToNotMoveTheOriginalAttachementButToDoACopyInstead(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $this->file_updater->shouldReceive('update')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfNoArtifactIdInTheRequest(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $this->request = Mockery::mock(Codendi_Request::class);
        $this->request->shouldReceive('get')->with('from_artifact_id')->andReturn(false);
        $this->request->shouldReceive('get')->with('from_changeset_id')->andReturn($this->changeset_id);
        $this->request->shouldReceive('get')->with('artifact')->andReturn($this->submitted_values);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->never();
        $this->xml_updater->shouldReceive('update')->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfNoChangesetIdInTheRequest(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $this->request = Mockery::mock(Codendi_Request::class);
        $this->request->shouldReceive('get')->with('from_artifact_id')->andReturn($this->artifact_id);
        $this->request->shouldReceive('get')->with('from_changeset_id')->andReturn(false);
        $this->request->shouldReceive('get')->with('artifact')->andReturn($this->submitted_values);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->never();
        $this->xml_updater->shouldReceive('update')->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfNoSubmittedValuesInTheRequest(): void
    {
        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $this->request = Mockery::mock(Codendi_Request::class);
        $this->request->shouldReceive('get')->with('from_artifact_id')->andReturn($this->artifact_id);
        $this->request->shouldReceive('get')->with('from_changeset_id')->andReturn($this->changeset_id);
        $this->request->shouldReceive('get')->with('artifact')->andReturn($this->changeset_id);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->never();
        $this->xml_updater->shouldReceive('update')->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfArtifactDoesNotBelongToTracker(): void
    {
        $another_tracker = Mockery::mock(Tracker::class);
        $another_tracker->shouldReceive('getId')->andReturn(111);
        $artifact_in_another_tracker = Mockery::mock(Artifact::class);
        $artifact_in_another_tracker->shouldReceive('getTracker')->andReturn($another_tracker);

        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->withArgs([$this->user, 666])
            ->andReturn($artifact_in_another_tracker);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $this->request = Mockery::mock(Codendi_Request::class);
        $this->request->shouldReceive('get')->with('from_artifact_id')->andReturn(666);
        $this->request->shouldReceive('get')->with('from_changeset_id')->andReturn($this->changeset_id);
        $this->request->shouldReceive('get')->with('artifact')->andReturn($this->changeset_id);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects(self::once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->never();
        $this->xml_updater->shouldReceive('update')->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItCreatesACommentChangesetContainingAllTheErrorRaisedDuringTheMigrationProcess(): void
    {
        $xml_content = <<<XML
            <artifacts>
                <artifact id="123" tracker_id="1">
                    <changeset>
                        <submitted_by format="id">101</submitted_by>
                        <submitted_on format="ISO8601">2016-02-15T10:34:38+00:00</submitted_on>
                    </changeset>
                </artifact>
            </artifacts>
XML;
        $xml         = new SimpleXMLElement($xml_content);
        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->andReturn($xml);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);
        $this->tracker->shouldReceive('getWorkflow')->andReturn(Mockery::spy(\Workflow::class));
        $this->tracker_factory->shouldReceive('getTrackerById')->andReturn($this->tracker);
        $this->xml_importer->shouldReceive('importBareArtifact')->andReturn($this->a_mocked_artifact);

        $this->a_mocked_artifact->shouldReceive('createNewChangesetWithoutRequiredValidation')
            ->withArgs([[], Mockery::any(), $this->user, true, Tracker_Artifact_Changeset_Comment::TEXT_COMMENT])
            ->once();

        $this->event_manager->expects(self::once())->method('dispatch');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItImportsTheArtifactsUsingTheXmlImporter(): void
    {
        $xml_content = <<<XML
            <artifacts>
                <artifact id="123" tracker_id="1">
                    <changeset>
                        <submitted_by format="id">101</submitted_by>
                        <submitted_on format="ISO8601">2016-02-15T10:34:38+00:00</submitted_on>
                    </changeset>
                </artifact>
                <artifact id="456" tracker_id="1">
                    <changeset>
                        <submitted_by format="id">101</submitted_by>
                        <submitted_on format="ISO8601">2016-02-15T10:35:38+00:00</submitted_on>
                    </changeset>
                </artifact>
                <artifact id="789" tracker_id="2">
                    <changeset>
                        <submitted_by format="id">101</submitted_by>
                        <submitted_on format="ISO8601">2016-02-15T10:36:38+00:00</submitted_on>
                    </changeset>
                </artifact>
            </artifacts>
XML;
        $xml         = new SimpleXMLElement($xml_content);
        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->andReturn($xml);

        $this->tracker->shouldReceive('userCanSubmitArtifact')->withArgs([$this->user])->andReturn(true);

        $tracker1 = Mockery::mock(Tracker::class);
        $tracker1->shouldReceive('getId')->andReturn(1);
        $tracker1->shouldReceive('getWorkflow')->andReturn(Mockery::spy(\Workflow::class));
        $tracker2 = Mockery::mock(Tracker::class);
        $tracker2->shouldReceive('getId')->andReturn(2);
        $tracker2->shouldReceive('getWorkflow')->andReturn(Mockery::spy(\Workflow::class));
        $this->tracker_factory->shouldReceive('getTrackerById')->withArgs([1])->andReturn($tracker1);
        $this->tracker_factory->shouldReceive('getTrackerById')->withArgs([2])->andReturn($tracker2);

        $artifact123 = Mockery::mock(Artifact::class);
        $artifact123->shouldReceive('getId')->andReturn(123);
        $artifact123->shouldReceive('getTracker')->andReturn($tracker1);
        $artifact456 = Mockery::mock(Artifact::class);
        $artifact456->shouldReceive('getId')->andReturn(456);
        $artifact456->shouldReceive('getTracker')->andReturn($tracker1);
        $artifact789 = Mockery::mock(Artifact::class);
        $artifact789->shouldReceive('getId')->andReturn(789);
        $artifact789->shouldReceive('getTracker')->andReturn($tracker2);

        $artifact123->shouldReceive('createNewChangesetWithoutRequiredValidation')->once();

        $this->xml_importer->shouldReceive('importBareArtifact')
            ->with(
                $tracker1,
                Mockery::on(
                    function (SimpleXMLElement $val) {
                        return (int) $val['id'] === 123;
                    }
                ),
                Mockery::on(
                    function ($element) {
                        return is_a($element, \Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig::class);
                    }
                )
            )
            ->andReturn($artifact123)->once();
        $this->xml_importer->shouldReceive('importBareArtifact')
            ->with(
                $tracker1,
                Mockery::on(
                    function (SimpleXMLElement $val) {
                        return (int) $val['id'] === 456;
                    }
                ),
                Mockery::on(
                    function ($element) {
                        return is_a($element, \Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig::class);
                    }
                )
            )
            ->andReturn($artifact456)->once();
        $this->xml_importer->shouldReceive('importBareArtifact')
            ->with(
                $tracker2,
                Mockery::on(
                    function (SimpleXMLElement $val) {
                        return (int) $val['id'] === 789;
                    }
                ),
                Mockery::on(
                    function ($element) {
                        return is_a($element, \Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig::class);
                    }
                )
            )
            ->andReturn($artifact789)->once();

        $this->xml_importer->shouldReceive('importChangesets')
            ->with(
                $artifact123,
                Mockery::on(
                    function (SimpleXMLElement $val) {
                        return (int) $val['id'] === 123;
                    }
                ),
                Mockery::any(),
                Mockery::type(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
                Mockery::type(\Tuleap\Tracker\XML\Importer\ImportedChangesetMapping::class),
                Mockery::type(\Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig::class)
            )->once();
        $this->xml_importer->shouldReceive('importChangesets')
            ->with(
                $artifact456,
                Mockery::on(
                    function (SimpleXMLElement $val) {
                        return (int) $val['id'] === 456;
                    }
                ),
                Mockery::any(),
                Mockery::type(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
                Mockery::type(\Tuleap\Tracker\XML\Importer\ImportedChangesetMapping::class),
                Mockery::type(\Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig::class)
            )->once();
        $this->xml_importer->shouldReceive('importChangesets')
            ->with(
                $artifact789,
                Mockery::on(
                    function (SimpleXMLElement $val) {
                        return (int) $val['id'] === 789;
                    }
                ),
                Mockery::any(),
                Mockery::type(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
                Mockery::type(\Tuleap\Tracker\XML\Importer\ImportedChangesetMapping::class),
                Mockery::type(\Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig::class)
            )->once();

        $this->event_manager->expects(self::once())->method('dispatch');

        $this->action->process($this->layout, $this->request, $this->user);
    }
}
