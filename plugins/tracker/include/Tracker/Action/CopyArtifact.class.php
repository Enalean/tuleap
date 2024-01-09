<?php
/**
 * Copyright (c) Enalean, 2014, Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\XMLImport\MoveImportConfig;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;

class Tracker_Action_CopyArtifact
{
    /**
     * @var Tracker_XML_Importer_ArtifactImportedMapping
     */
    private $artifacts_imported_mapping;

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
        private Tracker_XML_Exporter_ChildrenXMLExporter $children_xml_exporter,
        Tracker_XML_Importer_ArtifactImportedMapping $artifacts_imported_mapping,
        Tracker_XML_Importer_CopyArtifactInformationsAggregator $logger,
        private TrackerFactory $tracker_factory,
        private EventDispatcherInterface $event_dispatcher,
    ) {
        $this->tracker                    = $tracker;
        $this->artifact_factory           = $artifact_factory;
        $this->xml_exporter               = $xml_exporter;
        $this->xml_importer               = $xml_importer;
        $this->xml_updater                = $xml_updater;
        $this->file_updater               = $file_updater;
        $this->artifacts_imported_mapping = $artifacts_imported_mapping;
        $this->logger                     = $logger;
    }

    public function process(
        Tracker_IDisplayTrackerLayout $layout,
        Codendi_Request $request,
        PFUser $current_user,
    ) {
        if (! $this->tracker->userCanSubmitArtifact($current_user)) {
            $this->logsErrorAndRedirectToTracker(
                dgettext('tuleap-tracker', 'Access denied. You don\'t have permissions to perform this action.')
            );
            return;
        }
        $from_artifact = $this->artifact_factory->getArtifactByIdUserCanView($current_user, $request->get('from_artifact_id'));
        if (! $from_artifact || $from_artifact->getTracker() !== $this->tracker) {
            $this->logsErrorAndRedirectToTracker(
                dgettext('tuleap-tracker', 'Missing Parameters')
            );
            return;
        }

        $from_changeset = $from_artifact->getChangeset($request->get('from_changeset_id'));
        if (! $from_changeset) {
            $this->logsErrorAndRedirectToTracker(
                dgettext('tuleap-tracker', 'Missing Parameters')
            );
            return;
        }

        $submitted_values = $request->get('artifact');
        if (! is_array($submitted_values)) {
            $this->logsErrorAndRedirectToTracker(
                dgettext('tuleap-tracker', 'Missing Parameters')
            );
            return;
        }

        try {
            $this->processCopy($from_changeset, $current_user, $submitted_values);
        } catch (Tracker_XML_Exporter_TooManyChildrenException $exception) {
            $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-tracker', 'The artifact has too many children (limit is set to %1$s).'), Tracker_XML_ChildrenCollector::MAX));
            $this->redirectToArtifact($from_artifact);
        }
    }

    private function processCopy(
        Tracker_Artifact_Changeset $from_changeset,
        PFUser $current_user,
        array $submitted_values,
    ) {
        $current_time  = (new DateTimeImmutable())->setTimestamp($_SERVER['REQUEST_TIME']);
        $xml_artifacts = $this->getXMLRootNode();

        $xml_artifacts = $this->xml_exporter->exportSnapshotWithoutComments(
            $xml_artifacts,
            $from_changeset
        );

        $this->file_updater->update($xml_artifacts->artifact);

        $this->xml_updater->update(
            $this->tracker,
            $xml_artifacts->artifact,
            $submitted_values,
            $current_user,
            $current_time->getTimestamp()
        );

        $this->children_xml_exporter->exportChildren($xml_artifacts);

        if (count($xml_artifacts->artifact) < 1) {
            $this->logsErrorAndRedirectToTracker(
                sprintf(dgettext('tuleap-tracker', 'An error occured while creating a copy of the artifact #%1$s.'), $from_changeset->getArtifact()->getId())
            );
            return;
        }

        $xml_field_mapping = new TrackerXmlFieldsMapping_InSamePlatform();

        $no_child = count($xml_artifacts->artifact) == 1;
        if ($no_child) {
            $this->removeArtLinksValueNodeFromXML($xml_artifacts);
        }

        $new_artifacts = $this->importBareArtifacts($current_user, $current_time, $xml_artifacts);

        if ($new_artifacts == null) {
            $this->logsErrorAndRedirectToTracker(
                sprintf(dgettext('tuleap-tracker', 'An error occured while creating a copy of the artifact #%1$s.'), $from_changeset->getArtifact()->getId())
            );
            return;
        }

        $this->importChangesets($xml_artifacts, $new_artifacts, $xml_field_mapping);
        $this->addSummaryCommentChangeset($new_artifacts[0], $current_user, $from_changeset);
        $this->event_dispatcher->dispatch(
            new \Tuleap\Tracker\Action\AfterArtifactCopiedEvent(
                $this->artifacts_imported_mapping,
                $from_changeset->getTracker()->getProject(),
            )
        );

        $this->redirectToArtifact($new_artifacts[0]);
    }

    /**
     * @return Artifact[]|null null in case of error
     */
    private function importBareArtifacts(
        PFUser $current_user,
        DateTimeImmutable $imported_at,
        SimpleXMLElement $xml_artifacts,
    ) {
        $new_artifacts = [];
        foreach ($xml_artifacts->children() as $xml_artifact) {
            $tracker            = $this->tracker_factory->getTrackerById((int) $xml_artifact['tracker_id']);
            $tracker_xml_config = new \Tuleap\Tracker\Artifact\XMLImport\TrackerXmlImportConfig(
                $current_user,
                $imported_at,
                MoveImportConfig::buildForRegularImport(),
                false
            );
            if ($tracker === null) {
                return null;
            }
            $artifact = $this->xml_importer->importBareArtifact($tracker, $xml_artifact, $tracker_xml_config);
            if (! $artifact) {
                return null;
            } else {
                $new_artifacts[] = $artifact;
                $this->artifacts_imported_mapping->add((int) $xml_artifact['id'], $artifact->getId());
            }
        }
        return $new_artifacts;
    }

    private function importChangesets(SimpleXMLElement $xml_artifacts, array $new_artifacts, TrackerXmlFieldsMapping_InSamePlatform $xml_field_mapping)
    {
        $extraction_path = '';
        foreach (iterator_to_array($xml_artifacts->artifact, false) as $i => $xml_artifact) {
            $tracker_id = (int) $xml_artifact['tracker_id'];
            $tracker    = $this->tracker_factory->getTrackerById($tracker_id);
            if ($tracker === null) {
                throw new \RuntimeException(sprintf('Cannot find tracker #%d to import changesets', $tracker_id));
            }
            $tracker->getWorkflow()->disable();
            $fields_data_builder = $this->xml_importer->createFieldsDataBuilder(
                $tracker,
                $xml_artifact,
                $extraction_path,
                $xml_field_mapping,
                $this->artifacts_imported_mapping
            );
            $config              = new \Tuleap\Project\XML\Import\ImportConfig();
            $this->xml_importer->importChangesets(
                $new_artifacts[$i],
                $xml_artifact,
                $fields_data_builder,
                new CreatedFileURLMapping(),
                new \Tuleap\Tracker\XML\Importer\ImportedChangesetMapping(),
                new TrackerNoXMLImportLoggedConfig()
            );
        }
    }

    private function addSummaryCommentChangeset(
        Artifact $artifact,
        PFUser $user,
        Tracker_Artifact_Changeset $from_changeset,
    ) {
        $original_artifact = $from_changeset->getArtifact();
        $comment           = $this->logger->getAllLogs();
        $comment[]         = sprintf(dgettext('tuleap-tracker', 'Copy of %1$s #%2$s is finished.'), $original_artifact->getTracker()->getItemName(), $original_artifact->getId());
        $artifact->createNewChangesetWithoutRequiredValidation(
            [],
            implode("\n", $comment),
            $user,
            true,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );
    }

    private function removeArtLinksValueNodeFromXML(SimpleXMLElement &$xml_artifacts)
    {
        $xml_artifact = $xml_artifacts->artifact[0];
        foreach ($xml_artifact->changeset as $xml_changeset) {
            foreach ($xml_changeset->field_change as $xml_field_change) {
                if ($xml_field_change['type'] == 'art_link') {
                    $dom = dom_import_simplexml($xml_field_change);
                    foreach ($dom->childNodes as $child_node) {
                        $dom->removeChild($child_node);
                    }
                }
            }
        }
    }

    private function redirectToTracker()
    {
        $url = TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId();
        $GLOBALS['Response']->redirect($url);
    }

    private function redirectToArtifact(Artifact $artifact)
    {
        $url = TRACKER_BASE_URL . '/?aid=' . $artifact->getId();
        $GLOBALS['Response']->redirect($url);
    }

    private function getXMLRootNode()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><artifacts />';

        return new SimpleXMLElement($xml);
    }

    private function logsErrorAndRedirectToTracker($message)
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $message);
        $this->redirectToTracker();
    }
}
