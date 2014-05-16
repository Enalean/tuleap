<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Action_CopyArtifactTest extends TuleapTestCase {

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Action_CopyArtifact */
    private $action;

    /** @var Codendi_Request */
    private $request;

    /** @var Tracker_XMLExporter_ArtifactXMLExporter */
    private $xml_exporter;

    /** @var int */
    private $changeset_id = 101;

    /** @var Tracker_Artifact */
    private $from_artifact;

    /** @var Tracker_Artifact */
    private $new_artifact;

    /** @var int */
    private $new_artifact_id;

    /** @var Tracker_IDisplayTrackerLayout */
    private $layout;

    /** @var Tracker_Artifact_XMLImport */
    private $xml_importer;

    public function setUp() {
        parent::setUp();

        $artifact_id           = 123;
        $this->new_artifact_id = 456;

        $this->tracker       = aMockTracker()->withId(1)->build();
        $this->from_artifact = anArtifact()->withId($artifact_id)->build();
        $this->new_artifact  = anArtifact()->withId($this->new_artifact_id)->build();
        $this->layout        = mock('Tracker_IDisplayTrackerLayout');
        $this->user          = mock('PFUser');
        $this->xml_exporter  = mock('Tracker_XMLExporter_ArtifactXMLExporter');
        $this->xml_importer  = mock('Tracker_Artifact_XMLImport');

        $artifact_factory   = aMockArtifactFactory()
            ->withArtifact($this->from_artifact)
            ->build();

        $this->request = aRequest()
            ->with('from_artifact_id',  $artifact_id)
            ->with('from_changeset_id', $this->changeset_id)
            ->build();

        $this->action = new Tracker_Action_CopyArtifact(
            $this->tracker,
            $artifact_factory,
            $this->xml_exporter,
            $this->xml_importer
        );
    }

    public function itExportsTheRequiredSnapshotArtifact() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->xml_exporter)->exportSnapshotWithoutComments('*', $this->from_artifact, $this->changeset_id)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itImportTheXMLArtifactWithEmptyExtractionPath() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);

        expect($this->xml_importer)->importOneArtifactFromXML($this->tracker, '*', '')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itRedirectsToTheNewArtifact() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importOneArtifactFromXML()->returns($this->new_artifact);

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?aid=456')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNothingAndRedirectsToTheTrackerIfCannotSubmit() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(false);

        expect($this->xml_exporter)->exportSnapshotWithoutComments()->never();
        expect($this->xml_importer)->importOneArtifactFromXML()->never();

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itRedirectsToTheTrackerIfXMLImportFailed() {
        stub($this->tracker)->userCanSubmitArtifact($this->user)->returns(true);
        stub($this->xml_importer)->importOneArtifactFromXML()->returns(null);

        expect($GLOBALS['Response'])->redirect(TRACKER_BASE_URL .'/?tracker=1')->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }
}