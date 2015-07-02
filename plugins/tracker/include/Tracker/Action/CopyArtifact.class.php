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

class Tracker_Action_CopyArtifact {

    /**
     * @var Tracker_XML_Importer_ArtifactImportedMapping
     */
    private $artifacts_imported_mapping;

    /**
     * @var Tracker_XML_Importer_ChildrenXMLImporter
     */
    private $children_xml_importer;

    /**
     * @var Tracker_XML_Updater_TemporaryFileXMLUpdater
     */
    private $file_updater;

    /**
     * @var Tracker_XML_Updater_ChangesetXMLUpdater
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
     * @var Tracker_XML_Exporter_ArtifactXMLExporter
     */
    private $xml_exporter;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_XML_Importer_CopyArtifactInformationsAggregator */
    private $logger;

    public function __construct(
        Tracker $tracker,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_XML_Exporter_ArtifactXMLExporter $xml_exporter,
        Tracker_Artifact_XMLImport $xml_importer,
        Tracker_XML_Updater_ChangesetXMLUpdater $xml_updater,
        Tracker_XML_Updater_TemporaryFileXMLUpdater $file_updater,
        Tracker_XML_Exporter_ChildrenXMLExporter $children_xml_exporter,
        Tracker_XML_Importer_ChildrenXMLImporter $children_xml_importer,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_imported_mapping,
        Tracker_XML_Importer_CopyArtifactInformationsAggregator $logger
    ) {
        $this->tracker                    = $tracker;
        $this->artifact_factory           = $artifact_factory;
        $this->xml_exporter               = $xml_exporter;
        $this->xml_importer               = $xml_importer;
        $this->xml_updater                = $xml_updater;
        $this->file_updater               = $file_updater;
        $this->children_xml_exporter      = $children_xml_exporter;
        $this->children_xml_importer      = $children_xml_importer;
        $this->artifacts_imported_mapping = $artifacts_imported_mapping;
        $this->logger                     = $logger;
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

        try {
            $this->processCopy($from_changeset, $current_user, $submitted_values);
        } catch (Tracker_XML_Exporter_TooManyChildrenException $exception) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_artifact', 'copy_too_many_children', array(Tracker_XML_ChildrenCollector::MAX)));
            $this->redirectToArtifact($from_artifact);
        }
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

        $this->file_updater->update($xml_artifacts->artifact);

        $this->xml_updater->update(
            $this->tracker,
            $xml_artifacts->artifact,
            $submitted_values,
            $current_user,
            $_SERVER['REQUEST_TIME']
        );

        $this->children_xml_exporter->exportChildren($xml_artifacts);

        $extraction_path   = '';
        $xml_field_mapping = new TrackerXmlFieldsMapping_InSamePlatform();

        $artifact = $this->xml_importer->importOneArtifactFromXML(
            $this->tracker,
            $xml_artifacts->artifact[0],
            $extraction_path,
            $xml_field_mapping
        );

        if ($artifact) {
            $this->artifacts_imported_mapping->add($from_changeset->getArtifact()->getId(), $artifact->getId());
            $this->children_xml_importer->importChildren(
                $this->artifacts_imported_mapping,
                $xml_artifacts,
                $extraction_path,
                $artifact,
                $current_user
            );
            $this->addSummaryCommentChangeset($artifact, $current_user, $from_changeset);
            $this->redirectToArtifact($artifact);
        } else {
            $this->logsErrorAndRedirectToTracker(
                'plugin_tracker',
                'error_create_copy',
                $from_changeset->getArtifact()->getId()
            );
        }
    }

    private function addSummaryCommentChangeset(
        Tracker_Artifact $artifact,
        PFUser $user,
        Tracker_Artifact_Changeset $from_changeset
    ) {
        $original_artifact = $from_changeset->getArtifact();
        $comment           = $this->logger->getAllLogs();
        $comment[]         = $GLOBALS['Language']->getText(
            'plugin_tracker_artifact',
            'copy_artifact_finished',
            array(
                $original_artifact->getTracker()->getItemName(),
                $original_artifact->getId()
            )
        );

        $artifact->createNewChangeset(
            array(),
            implode("\n",$comment),
            $user,
            true,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );
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
