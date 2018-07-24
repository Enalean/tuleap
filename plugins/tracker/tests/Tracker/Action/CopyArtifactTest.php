<?php
/**
 * Copyright (c) Enalean, 2014, 2015. All Rights Reserved.
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
require_once __DIR__.'/../../bootstrap.php';

class Tracker_Action_CopyArtifactTest extends TuleapTestCase {

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Action_CopyArtifact */
    private $action;

    /** @var Codendi_Request */
    private $request;

    /** @var Tracker_XML_Exporter_ArtifactXMLExporter */
    private $xml_exporter;

    /** @var int */
    private $changeset_id = 101;

    /** @var Tracker_Artifact */
    private $from_artifact;

    /** @var Tracker_Artifact */
    private $new_artifact;

    /** @var int */
    private $artifact_id = 123;

    /** @var int */
    private $new_artifact_id = 456;

    /** @var Tracker_IDisplayTrackerLayout */
    private $layout;

    /** @var Tracker_Artifact_XMLImport */
    private $xml_importer;

    /** @var Tracker_XML_Updater_ChangesetXMLUpdater */
    private $xml_updater;

    /** @var array */
    private $submitted_values;

    /** @var Tracker_Artifact_Changeset */
    private $from_changeset;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_XML_Updater_TemporaryFileXMLUpdater */
    private $file_updater;

    /** @var Tracker_XML_Exporter_ChildrenXMLExporter */
    private $children_xml_exporter;

    /** @var Tracker_XML_Importer_ArtifactImportedMapping */
    private $artifacts_imported_mapping;

    /** @var Tracker_XML_Importer_CopyArtifactInformationsAggregator */
    private $logger;

    /** @var Tracker_Artifact */
    private $a_mocked_artifact;

    /** @var TrackerFactory */
    private $tracker_factory;

    public function setUp() {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $changeset_factory    = \Mockery::spy(\Tracker_Artifact_ChangesetFactory::class);
        $this->tracker        = aMockeryTracker()->withId(1)->build();
        $this->new_artifact   = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->new_artifact->setId($this->new_artifact_id);
        $this->layout         = \Mockery::spy(\Tracker_IDisplayTrackerLayout::class);
        $this->user           = \Mockery::spy(\PFUser::class);
        $this->xml_exporter   = \Mockery::spy(\Tracker_XML_Exporter_ArtifactXMLExporter::class);
        $this->xml_importer   = \Mockery::spy(\Tracker_Artifact_XMLImport::class);
        $this->xml_updater    = \Mockery::spy(\Tracker_XML_Updater_ChangesetXMLUpdater::class);
        $this->file_updater   = \Mockery::spy(\Tracker_XML_Updater_TemporaryFileXMLUpdater::class);
        $this->from_changeset = mockery_stub(\Tracker_Artifact_Changeset::class)->getId()->returns($this->changeset_id);
        $this->from_artifact  = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->from_artifact->setId($this->artifact_id);
        $this->from_artifact->setTracker($this->tracker);
        $this->from_artifact->setChangesets(array($this->changeset_id => $this->from_changeset));
        stub($this->from_artifact)->getChangesetFactory()->returns($changeset_factory);
        stub($this->from_changeset)->getArtifact()->returns($this->from_artifact);
        $this->children_xml_exporter       = \Mockery::spy(\Tracker_XML_Exporter_ChildrenXMLExporter::class);
        $this->artifacts_imported_mapping  = \Mockery::spy(\Tracker_XML_Importer_ArtifactImportedMapping::class);

        $backend_logger = \Mockery::spy(\BackendLogger::class);
        $this->logger   = new Tracker_XML_Importer_CopyArtifactInformationsAggregator($backend_logger);

        $this->submitted_values = array();

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        stub($this->artifact_factory)
            ->getArtifactByIdUserCanView($this->user, $this->artifact_id)
            ->returns($this->from_artifact);

        $this->request = aRequest()
            ->with('from_artifact_id',  $this->artifact_id)
            ->with('from_changeset_id', $this->changeset_id)
            ->with('artifact',          $this->submitted_values)
            ->build();

        $this->tracker_factory = \Mockery::spy(\TrackerFactory::class);

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
            $this->tracker_factory
        );

        $this->default_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifacts />');

        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->andReturns($this->default_xml)->byDefault();

        $this->a_mocked_artifact = \Mockery::spy(\Tracker_Artifact::class);

        stub($this->xml_importer)->createFieldsDataBuilder()->returns(\Mockery::spy(Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder::class));
    }

    public function itExportsTheRequiredSnapshotArtifact() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importBareArtifact('*', '*')->returns(\Mockery::spy(\Tracker_Artifact::class));
        expect($this->xml_exporter)->exportSnapshotWithoutComments('*', $this->from_changeset)->once()->returns($this->default_xml);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itRedirectsToTheNewArtifact() {
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
        $xml = new SimpleXMLElement($xml_content);
        $this->xml_exporter->shouldReceive('exportSnapshotWithoutComments')->once()->andReturns($xml);
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->tracker)->getWorkflow()->returns(\Mockery::spy(\Workflow::class));
        stub($this->tracker_factory)->getTrackerById()->returns($this->tracker);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importBareArtifact()->returns($this->new_artifact);

        expect($this->new_artifact)->createNewChangesetWhitoutRequiredValidation()->once();

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?aid=456')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNothingAndRedirectsToTheTrackerIfCannotSubmit() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(false);

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itRedirectsToTheTrackerIfXMLImportFailed() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itUpdatesTheXMLWithIncomingValues() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->xml_updater)->update(
            $this->tracker,
            '*',
            $this->submitted_values,
            $this->user,
            $_SERVER['REQUEST_TIME']
        )->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itUpdateTheXMLToNotMoveTheOriginalAttachementButToDoACopyInstead() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->file_updater)->update()->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfNoArtifactIdInTheRequest() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_changeset_id', $this->changeset_id)
            ->with('artifact',          $this->submitted_values)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfNoChangesetIdInTheRequest() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_artifact_id', $this->artifact_id)
            ->with('artifact',         $this->submitted_values)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfNoSubmittedValuesInTheRequest() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_artifact_id',  $this->artifact_id)
            ->with('from_changeset_id', $this->changeset_id)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itErrorsIfArtifactDoesNotBelongToTracker() {
        $another_tracker = aTracker()->withId(111)->build();
        $artifact_in_another_tracker = anArtifact()->withTracker($another_tracker)->build();
        stub($this->artifact_factory)
            ->getArtifactByIdUserCanView($this->user, 666)
            ->returns($artifact_in_another_tracker);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $this->request = aRequest()
            ->with('from_artifact_id', 666)
            ->build();

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_updater)->update()->never();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itCreatesACommentChangesetContainingAllTheErrorRaisedDuringTheMigrationProcess() {
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
        $xml = new SimpleXMLElement($xml_content);
        stub($this->xml_exporter)->exportSnapshotWithoutComments()->returns($xml);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->tracker)->getWorkflow()->returns(\Mockery::spy(\Workflow::class));
        stub($this->tracker_factory)->getTrackerById()->returns($this->tracker);
        stub($this->xml_importer)->importBareArtifact()->returns($this->a_mocked_artifact);

        expect($this->a_mocked_artifact)->createNewChangesetWhitoutRequiredValidation(array(), '*', $this->user, true, Tracker_Artifact_Changeset_Comment::TEXT_COMMENT)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itImportsTheArtifactsUsingTheXmlImporter() {
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
        $xml = new SimpleXMLElement($xml_content);
        stub($this->xml_exporter)->exportSnapshotWithoutComments()->returns($xml);

        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        $tracker1 = aMockTracker()->withId(1)->build();
        stub($tracker1)->getWorkflow()->returns(\Mockery::spy(\Workflow::class));
        $tracker2 = aMockTracker()->withId(2)->build();
        stub($tracker2)->getWorkflow()->returns(\Mockery::spy(\Workflow::class));
        stub($this->tracker_factory)->getTrackerById(1)->returns($tracker1);
        stub($this->tracker_factory)->getTrackerById(2)->returns($tracker2);

        $artifact123  = aMockArtifact()->withId(123)->withTracker($tracker1)->build();
        $artifact456  = aMockArtifact()->withId(456)->withTracker($tracker1)->build();
        $artifact789  = aMockArtifact()->withId(789)->withTracker($tracker2)->build();

        $this->xml_importer->shouldReceive('importBareArtifact')
            ->with(
                $tracker1,
                \Mockery::on(function (SimpleXMLElement $val) { return (int)$val['id'] === 123;}),
                Mockery::on(function ($element) { return is_a($element, Tuleap\Project\XML\Import\ImportConfig::class); })
            )
            ->andReturn($artifact123)->once();
        $this->xml_importer->shouldReceive('importBareArtifact')
            ->with(
                $tracker1,
                \Mockery::on(function (SimpleXMLElement $val) { return (int)$val['id'] === 456;}),
                Mockery::on(function ($element) { return is_a($element, Tuleap\Project\XML\Import\ImportConfig::class); })
            )
            ->andReturn($artifact456)->once();
        $this->xml_importer->shouldReceive('importBareArtifact')
            ->with(
                $tracker2,
                \Mockery::on(function (SimpleXMLElement $val) { return (int)$val['id'] === 789;}),
                Mockery::on(function ($element) { return is_a($element, Tuleap\Project\XML\Import\ImportConfig::class); })
            )
            ->andReturn($artifact789)->once();

        $this->xml_importer->shouldReceive('importChangesets')
            ->with(
                $artifact123,
                \Mockery::on(function (SimpleXMLElement $val) { return (int)$val['id'] === 123;}),
                \Mockery::any(),
                Mockery::on(function ($element) { return is_a($element, Tuleap\Project\XML\Import\ImportConfig::class); }))
            ->once();
        $this->xml_importer->shouldReceive('importChangesets')
            ->with(
                $artifact456,
                \Mockery::on(function (SimpleXMLElement $val) { return (int)$val['id'] === 456;}),
                \Mockery::any(),
                Mockery::on(function ($element) { return is_a($element, Tuleap\Project\XML\Import\ImportConfig::class); })
            )->once();
        $this->xml_importer->shouldReceive('importChangesets')
            ->with(
                $artifact789,
                \Mockery::on(function (SimpleXMLElement $val) { return (int)$val['id'] === 789;}),
                \Mockery::any(),
                Mockery::on(function ($element) { return is_a($element, Tuleap\Project\XML\Import\ImportConfig::class); })
            )->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }
}
