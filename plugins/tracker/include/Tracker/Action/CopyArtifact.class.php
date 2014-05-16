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

class Tracker_Action_CopyArtifact {

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var Tracker_Artifact_XMLImport
     */
    private $xml_importer;

    /**
     * @var Tracker_XMLExporter_ArtifactXMLExporter
     */
    private $xml_exporter;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        Tracker $tracker,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_XMLExporter_ArtifactXMLExporter $xml_exporter,
        Tracker_Artifact_XMLImport $xml_importer
    ) {
        $this->tracker          = $tracker;
        $this->artifact_factory = $artifact_factory;
        $this->xml_exporter     = $xml_exporter;
        $this->xml_importer     = $xml_importer;
    }

    public function process(
        Tracker_IDisplayTrackerLayout $layout,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        if ($this->tracker->userCanSubmitArtifact($current_user)) {
            $this->processCopy($layout, $request, $current_user);
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $this->redirectToTracker();
        }
    }

    private function processCopy(
        Tracker_IDisplayTrackerLayout $layout,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        $from_changeset_id = $request->get('from_changeset_id');
        $from_artifact_id  = $request->get('from_artifact_id');
        $from_artifact     = $this->artifact_factory->getArtifactById($from_artifact_id);

        $xml_artifacts = $this->getXMLRootNode();
        $this->xml_exporter->exportSnapshotWithoutComments(
            $xml_artifacts,
            $from_artifact,
            $from_changeset_id
        );

        $extraction_path = '';
        $artifact = $this->xml_importer->importOneArtifactFromXML(
            $this->tracker,
            $xml_artifacts->artifact,
            $extraction_path
        );
        if ($artifact) {
            $this->redirectToArtifact($artifact);
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugins_tracker', 'error_create_copy', $from_artifact_id)
            );
            $this->redirectToTracker();
        }
    }

    private function redirectToTracker() {
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId());
    }

    private function redirectToArtifact(Tracker_Artifact $artifact) {
        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?aid=' . $artifact->getId());
    }

    private function getXMLRootNode() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';

        return new SimpleXMLElement($xml);
    }
}