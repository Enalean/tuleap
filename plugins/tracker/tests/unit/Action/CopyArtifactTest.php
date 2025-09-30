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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\XML\Exporter\ArtifactXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChildrenXMLExporter;
use Tuleap\Tracker\Artifact\XMLImport\TrackerImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\DisplayTrackerLayoutStub;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\XML\Importer\ImportedChangesetMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Action_CopyArtifactTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalResponseMock;

    private SimpleXMLElement $default_xml;
    private PFUser $user;

    private Tracker&MockObject $tracker;
    private Tracker $another_tracker;
    private Artifact $artifact_in_another_tracker;

    private Tracker_Action_CopyArtifact $action;

    private Codendi_Request $request;

    private ArtifactXMLExporter&MockObject $xml_exporter;

    private int $changeset_id = 101;

    private Artifact&MockObject $from_artifact;

    private Artifact&MockObject $new_artifact;

    private int $artifact_id = 123;

    private int $new_artifact_id = 456;

    private Tracker_IDisplayTrackerLayout $layout;

    private Tracker_Artifact_XMLImport&MockObject $xml_importer;

    private Tracker_XML_Updater_ChangesetXMLUpdater&MockObject $xml_updater;

    private array $submitted_values;

    private Tracker_Artifact_Changeset $from_changeset;

    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    private Tracker_XML_Updater_TemporaryFileXMLUpdater&MockObject $file_updater;

    private ChildrenXMLExporter&MockObject $children_xml_exporter;

    private Tracker_XML_Importer_ArtifactImportedMapping&MockObject $artifacts_imported_mapping;

    private Tracker_XML_Importer_CopyArtifactInformationsAggregator $logger;

    private Artifact $a_mocked_artifact;

    private TrackerFactory&MockObject $tracker_factory;
    private EventManager&MockObject $event_manager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $changeset_factory = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $changeset_factory->method('getChangeset');

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getId')->willReturn(1);
        $this->tracker->method('getItemName');
        $this->tracker->method('getProject')->willReturn(
            \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build()
        );
        $this->new_artifact = $this->createPartialMock(Artifact::class, ['createNewChangesetWithoutRequiredValidation']);
        $this->new_artifact->setId($this->new_artifact_id);
        $this->layout        = DisplayTrackerLayoutStub::build();
        $this->user          = UserTestBuilder::buildWithDefaults();
        $this->xml_exporter  = $this->createMock(ArtifactXMLExporter::class);
        $this->xml_importer  = $this->createMock(Tracker_Artifact_XMLImport::class);
        $this->xml_updater   = $this->createMock(Tracker_XML_Updater_ChangesetXMLUpdater::class);
        $this->file_updater  = $this->createMock(Tracker_XML_Updater_TemporaryFileXMLUpdater::class);
        $this->from_artifact = $this->createPartialMock(Artifact::class, ['getChangesetFactory']);
        $this->from_artifact->setId($this->artifact_id);
        $this->from_artifact->setTracker($this->tracker);
        $this->from_changeset = ChangesetTestBuilder::aChangeset($this->changeset_id)->ofArtifact($this->from_artifact)->build();
        $this->from_artifact->setChangesets([$this->changeset_id => $this->from_changeset]);
        $this->from_artifact->method('getChangesetFactory')->willReturn($changeset_factory);
        $this->children_xml_exporter      = $this->createMock(ChildrenXMLExporter::class);
        $this->artifacts_imported_mapping = $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class);

        $this->children_xml_exporter->method('exportChildren');
        $this->artifacts_imported_mapping->method('add');

        $backend_logger = new \Psr\Log\NullLogger();
        $this->logger   = new Tracker_XML_Importer_CopyArtifactInformationsAggregator($backend_logger);

        $this->submitted_values = [];

        $this->another_tracker             = TrackerTestBuilder::aTracker()->withId(111)->build();
        $this->artifact_in_another_tracker = ArtifactTestBuilder::anArtifact(666)
            ->inTracker($this->another_tracker)
            ->build();

        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->artifact_factory
            ->method('getArtifactByIdUserCanView')
            ->willReturnCallback(fn (PFUser $user, $id) => match (true) {
                $user === $this->user && $id === $this->artifact_id => $this->from_artifact,
                $user === $this->user && $id === $this->artifact_in_another_tracker->getId() => $this->artifact_in_another_tracker,
                default => null,
            });

        $this->request = HTTPRequestBuilder::get()
            ->withParams([
                'from_artifact_id' => $this->artifact_id,
                'from_changeset_id' => $this->changeset_id,
                'artifact' => $this->submitted_values,
            ])->build();

        $this->tracker_factory = $this->createMock(\TrackerFactory::class);
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

        $this->a_mocked_artifact = $this->createMock(Artifact::class);
        $this->a_mocked_artifact->method('getId')->willReturn(444);

        $this->xml_importer->method('createFieldsDataBuilder')
            ->willReturn($this->createMock(Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder::class));
    }

    public function testItExportsTheRequiredSnapshotArtifact(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);
        $this->xml_importer->method('importBareArtifact')->willReturn($this->createMock(
            Artifact::class
        ));
        $this->xml_exporter
            ->expects($this->once())
            ->method('exportSnapshotWithoutComments')
            ->willReturnCallback(
                fn (SimpleXMLElement $artifacts_xml, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                    $this->from_changeset => $this->default_xml
                }
            );
        $this->xml_updater->method('update');
        $this->file_updater->method('update');

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
        $this->xml_exporter->expects($this->once())->method('exportSnapshotWithoutComments')->willReturn($xml);
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);
        $workflow = $this->createMock(\Workflow::class);
        $workflow->method('disable');
        $this->tracker->method('getWorkflow')->willReturn($workflow);
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);

        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);
        $this->xml_importer->method('importBareArtifact')->willReturn($this->new_artifact);
        $this->xml_importer->method('importChangesets');

        $this->new_artifact->expects($this->once())->method('createNewChangesetWithoutRequiredValidation');

        $this->event_manager->expects($this->once())->method('dispatch');

        $GLOBALS['Response']->expects($this->once())->method('redirect')->with(TRACKER_BASE_URL . '/?aid=456');

        $this->xml_updater->method('update');
        $this->file_updater->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNothingAndRedirectsToTheTrackerIfCannotSubmit(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(false);

        $this->xml_exporter->expects($this->never())->method('exportSnapshotWithoutComments');

        $GLOBALS['Response']->expects($this->once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItRedirectsToTheTrackerIfXMLImportFailed(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $this->xml_exporter->method('exportSnapshotWithoutComments')->willReturn($this->default_xml);

        $GLOBALS['Response']->expects($this->once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_updater->method('update');
        $this->file_updater->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItUpdatesTheXMLWithIncomingValues(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $this->xml_exporter->method('exportSnapshotWithoutComments')->willReturn($this->default_xml);

        $this->xml_updater->expects($this->once())->method('update')->with(
            $this->tracker,
            self::anything(),
            $this->submitted_values,
            $this->user,
            $_SERVER['REQUEST_TIME'],
        );
        $this->file_updater->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItUpdateTheXMLToNotMoveTheOriginalAttachementButToDoACopyInstead(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $this->xml_exporter->method('exportSnapshotWithoutComments')->willReturn($this->default_xml);

        $this->file_updater->expects($this->once())->method('update');

        $this->xml_updater->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfNoArtifactIdInTheRequest(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $this->request = HTTPRequestBuilder::get()
            ->withParams([
                'from_changeset_id' => $this->changeset_id,
                'artifact' => $this->submitted_values,
            ])->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects($this->once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->expects($this->never())->method('exportSnapshotWithoutComments');
        $this->xml_updater->expects($this->never())->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfNoChangesetIdInTheRequest(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $this->request = HTTPRequestBuilder::get()
            ->withParams([
                'from_artifact_id' => $this->artifact_id,
                'artifact' => $this->submitted_values,
            ])->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects($this->once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->expects($this->never())->method('exportSnapshotWithoutComments');
        $this->xml_updater->expects($this->never())->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfNoSubmittedValuesInTheRequest(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $this->request = HTTPRequestBuilder::get()
            ->withParams([
                'from_artifact_id' => $this->artifact_id,
                'from_changeset_id' => $this->changeset_id,
                'artifact' => $this->changeset_id,
            ])->build();

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects($this->once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->expects($this->never())->method('exportSnapshotWithoutComments');
        $this->xml_updater->expects($this->never())->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItErrorsIfArtifactDoesNotBelongToTracker(): void
    {
        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $this->request = HTTPRequestBuilder::get()
            ->withParams([
                'from_artifact_id' => $this->artifact_in_another_tracker->getId(),
                'from_changeset_id' => $this->changeset_id,
                'artifact' => $this->submitted_values,
            ])->build();


        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error');
        $GLOBALS['Response']->expects($this->once())->method('redirect')->with(TRACKER_BASE_URL . '/?tracker=1');

        $this->xml_exporter->expects($this->never())->method('exportSnapshotWithoutComments');
        $this->xml_updater->expects($this->never())->method('update');

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
        $this->xml_exporter->method('exportSnapshotWithoutComments')->willReturn($xml);

        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);
        $workflow = $this->createMock(\Workflow::class);
        $workflow->method('disable');
        $this->tracker->method('getWorkflow')->willReturn($workflow);
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);
        $this->xml_importer->method('importBareArtifact')->willReturn($this->a_mocked_artifact);
        $this->xml_importer->method('importChangesets');

        $this->a_mocked_artifact
            ->expects($this->once())
            ->method('createNewChangesetWithoutRequiredValidation')
            ->with([], self::anything(), $this->user, true, CommentFormatIdentifier::TEXT);

        $this->event_manager->expects($this->once())->method('dispatch');

        $this->xml_updater->method('update');
        $this->file_updater->method('update');

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
        $this->xml_exporter->method('exportSnapshotWithoutComments')->willReturn($xml);

        $this->tracker->method('userCanSubmitArtifact')->with($this->user)->willReturn(true);

        $tracker1 = $this->createMock(Tracker::class);
        $tracker1->method('getId')->willReturn(1);
        $workflow1 = $this->createMock(\Workflow::class);
        $workflow1->method('disable');
        $tracker1->method('getWorkflow')->willReturn($workflow1);
        $tracker2 = $this->createMock(Tracker::class);
        $tracker2->method('getId')->willReturn(2);
        $workflow2 = $this->createMock(\Workflow::class);
        $workflow2->method('disable');
        $tracker2->method('getWorkflow')->willReturn($workflow2);
        $this->tracker_factory->method('getTrackerById')->willReturnCallback(static fn ($id) => match ($id) {
            1 => $tracker1,
            2 => $tracker2,
        });

        $artifact123 = $this->createMock(Artifact::class);
        $artifact123->method('getId')->willReturn(123);
        $artifact123->method('getTracker')->willReturn($tracker1);
        $artifact456 = $this->createMock(Artifact::class);
        $artifact456->method('getId')->willReturn(456);
        $artifact456->method('getTracker')->willReturn($tracker1);
        $artifact789 = $this->createMock(Artifact::class);
        $artifact789->method('getId')->willReturn(789);
        $artifact789->method('getTracker')->willReturn($tracker2);

        $artifact123->expects($this->once())->method('createNewChangesetWithoutRequiredValidation');

        $this->xml_importer
            ->expects($this->exactly(3))
            ->method('importBareArtifact')
            ->willReturnCallback(
                static fn (
                    Tracker $tracker,
                    SimpleXMLElement $xml_artifact,
                    TrackerXmlImportConfig $tracker_xml_config,
                ) => match (true) {
                    (int) $xml_artifact['id'] === 123 => $artifact123,
                    (int) $xml_artifact['id'] === 456 => $artifact456,
                    (int) $xml_artifact['id'] === 789 => $artifact789,
                }
            );

        $this->xml_importer
            ->expects($this->exactly(3))
            ->method('importChangesets')
            ->willReturnCallback(
                static fn (
                    Artifact $artifact,
                    SimpleXMLElement $xml_artifact,
                    Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder,
                    CreatedFileURLMapping $url_mapping,
                    ImportedChangesetMapping $changeset_id_mapping,
                    TrackerImportConfig $tracker_import_config,
                ) => match (true) {
                    $artifact === $artifact123 && (int) $xml_artifact['id'] === 123,
                    $artifact === $artifact456 && (int) $xml_artifact['id'] === 456,
                    $artifact === $artifact789 && (int) $xml_artifact['id'] === 789 => true,
                }
            );

        $this->event_manager->expects($this->once())->method('dispatch');

        $this->xml_updater->method('update');
        $this->file_updater->method('update');

        $this->action->process($this->layout, $this->request, $this->user);
    }
}
