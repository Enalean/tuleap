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
     * @var Tracker_XMLUpdater_ChangesetXMLUpdater
     */
    private $xml_updater;

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
        Tracker_Artifact_XMLImport $xml_importer,
        Tracker_XMLUpdater_ChangesetXMLUpdater $xml_updater
    ) {
        $this->tracker          = $tracker;
        $this->artifact_factory = $artifact_factory;
        $this->xml_exporter     = $xml_exporter;
        $this->xml_importer     = $xml_importer;
        $this->xml_updater      = $xml_updater;
    }

    public function process(
        Tracker_IDisplayTrackerLayout $layout,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        if (! $this->tracker->userCanSubmitArtifact($current_user)) {
            $this->logsErrorAndRedirectToTracker('plugin_tracker_admin', 'access_denied');
            return;
        }

        $from_artifact = $this->artifact_factory->getArtifactByIdUserCanView($current_user, $request->get('from_artifact_id'));
        if (! $from_artifact || $from_artifact->getTracker() !== $this->tracker) {
            $this->logsErrorAndRedirectToTracker('plugin_tracker_include_type', 'error_missing_param');
            return;
        }

        $from_changeset = $from_artifact->getChangeset($request->get('from_changeset_id'));
        if (! $from_changeset) {
            $this->logsErrorAndRedirectToTracker('plugin_tracker_include_type', 'error_missing_param');
            return;
        }

        $submitted_values = $request->get('artifact');
        if (! is_array($submitted_values)) {
            $this->logsErrorAndRedirectToTracker('plugin_tracker_include_type', 'error_missing_param');
            return;
        }

        $this->processCopy($from_changeset, $current_user, $submitted_values);
    }

    private function processCopy(
        Tracker_Artifact_Changeset $from_changeset,
        PFUser $current_user,
        array $submitted_values
    ) {
        $xml_artifacts = $this->getXMLRootNode();
        $this->xml_exporter->exportSnapshotWithoutComments(
            $xml_artifacts,
            $from_changeset
        );

        $this->xml_updater->update(
            $this->tracker,
            $xml_artifacts->artifact,
            $submitted_values,
            $current_user,
            $_SERVER['REQUEST_TIME']
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
            $this->logsErrorAndRedirectToTracker(
                'plugin_tracker',
                'error_create_copy',
                $from_changeset->getArtifact()->getId()
            );
        }
    }

    private function redirectToTracker() {
        $url = TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId();
        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToArtifact(Tracker_Artifact $artifact) {
        $url = TRACKER_BASE_URL . '/?aid=' . $artifact->getId();
        $GLOBALS['Response']->redirect($url);
    }

    private function getXMLRootNode() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';

        return new SimpleXMLElement($xml);
    }

    private function logsErrorAndRedirectToTracker(
        $language_first_key,
        $language_second_key,
        $language_params = array()
    ) {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText($language_first_key, $language_second_key, $language_params)
        );
        $this->redirectToTracker();
    }
}
