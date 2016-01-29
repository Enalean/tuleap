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

class Tracker_Artifact_XMLImport {

    /** @var boolean */
    private $send_notifications;

    /** @var XML_RNGValidator */
    private $rng_validator;

    /** @var Tracker_ArtifactCreator */
    private $artifact_creator;

    /** @var Tracker_Artifact_Changeset_NewChangesetCreatorBase */
    private $new_changeset_creator;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var User\XML\Import\IFindUserFromXMLReference */
    private $user_finder;

    /** @var Tracker_FormElement_Field_List_Bind_Static_ValueDao */
    private $static_value_dao;

    /** @var WrapperLogger */
    private $logger;

    public function __construct(
        XML_RNGValidator $rng_validator,
        Tracker_ArtifactCreator $artifact_creator,
        Tracker_Artifact_Changeset_NewChangesetCreatorBase $new_changeset_creator,
        Tracker_FormElementFactory $formelement_factory,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        Tracker_FormElement_Field_List_Bind_Static_ValueDao $static_value_dao,
        Logger $logger,
        $send_notifications
    ) {

        $this->rng_validator         = $rng_validator;
        $this->artifact_creator      = $artifact_creator;
        $this->new_changeset_creator = $new_changeset_creator;
        $this->formelement_factory   = $formelement_factory;
        $this->user_finder           = $user_finder;
        $this->static_value_dao      = $static_value_dao;
        $this->logger                = new WrapperLogger($logger, 'XML import');
        $this->send_notifications    = $send_notifications;
    }

    public function importFromArchive(Tracker $tracker, Tracker_Artifact_XMLImport_XMLImportZipArchive $archive) {
        $archive->extractFiles();
        $xml = simplexml_load_string($archive->getXML());

        $extraction_path   = $archive->getExtractionPath();
        $xml_field_mapping = new TrackerXmlFieldsMapping_InSamePlatform();

        $this->importFromXML($tracker, $xml, $extraction_path, $xml_field_mapping);

        $archive->cleanUp();
    }

    public function importFromFile(Tracker $tracker, $xml_file_path) {
        $xml_security      = new XML_Security();
        $xml               = $xml_security->loadFile($xml_file_path);
        $xml_file_path     = "";
        $xml_field_mapping = new TrackerXmlFieldsMapping_InSamePlatform();

        $this->importFromXML(
            $tracker,
            $xml,
            $xml_file_path,
            $xml_field_mapping
        );
    }

    public function importFromXML(
        Tracker $tracker,
        SimpleXMLElement $xml_element,
        $extraction_path,
        TrackerXmlFieldsMapping $xml_fields_mapping
    ) {
        $this->rng_validator->validate($xml_element, realpath(dirname(TRACKER_BASE_DIR) . '/www/resources/artifacts.rng'));
        foreach ($xml_element->artifact as $artifact) {
            $this->importOneArtifactFromXML($tracker, $artifact, $extraction_path, $xml_fields_mapping);
        }
    }

    /**
     * @return Tracker_Artifact|null The created artifact
     */
    public function importOneArtifactFromXML(
        Tracker $tracker,
        SimpleXMLElement $xml_artifact,
        $extraction_path,
        TrackerXmlFieldsMapping $xml_fields_mapping
    ) {
        try {
            $this->logger->info("Import {$xml_artifact['id']}");

            $files_importer = new Tracker_Artifact_XMLImport_CollectionOfFilesToImportInArtifact($xml_artifact);
            $fields_data_builder = new Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder(
                $this->formelement_factory,
                $this->user_finder,
                $tracker,
                $files_importer,
                $extraction_path,
                $this->static_value_dao,
                $this->logger,
                $xml_fields_mapping
            );

            $tracker->getWorkflow()->disable();

            return $this->importOneArtifact($tracker, $xml_artifact, $fields_data_builder);
        } catch (Tracker_Artifact_Exception_CannotCreateInitialChangeset $exception) {
            $this->logger->error("Impossible to create artifact: ".$exception->getMessage());
        } catch (Tracker_Artifact_Exception_EmptyChangesetException $exception) {
            $this->logger->error("Impossible to create artifact, there is no valid data to import for initial changeset: ".$exception->getMessage());
        } catch (Exception $exception) {
            $this->logger->error("".get_class($exception).': '.$exception->getMessage().' in '.$exception->getFile().' L'.$exception->getLine());
        }
    }

    /**
     * @return Tracker_Artifact|null The created artifact
     */
    private function importOneArtifact(
        Tracker $tracker,
        SimpleXMLElement $xml_artifact,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        $this->logger->info('art #'.(string)$xml_artifact['id'].' with '.count($xml_artifact->changeset).' changesets ');
        if (count($xml_artifact->changeset) > 0) {
            $changesets      = $this->getSortedBySubmittedOn($xml_artifact->changeset);
            $first_changeset = array_shift($changesets);
            $artifact        = $this->importInitialChangeset($tracker, $first_changeset, $fields_data_builder);
            $this->logger->info("--> new artifact {$artifact->getId()}");
            $this->logger->debug("changeset to create: ".count($changesets));
            if (count($changesets)) {
                $this->importRemainingChangesets($artifact, $changesets, $fields_data_builder);
            }
            return $artifact;
        }
    }

    private function getSortedBySubmittedOn(SimpleXMLElement $changesets) {
        $changeset_array = array();
        foreach ($changesets as $changeset) {
            $timestamp = $this->getSubmittedOn($changeset);
            if (! isset($changeset_array[$timestamp])) {
                $changeset_array[$timestamp] = array($changeset);
            } else {
                $changeset_array[$timestamp][] = $changeset;
            }
        }
        ksort($changeset_array, SORT_NUMERIC);
        return $this->flattenChangesetArray($changeset_array);
    }

    private function flattenChangesetArray(array $changesets_per_timestamp) {
        $changesets = array();
        foreach ($changesets_per_timestamp as $changeset_per_timestamp) {
            foreach ($changeset_per_timestamp as $changeset) {
                $changesets[] = $changeset;
            }
        }
        return $changesets;
    }

    private function importInitialChangeset(
        Tracker $tracker,
        SimpleXMLElement $xml_changeset,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        $submitted_by = $this->getSubmittedBy($xml_changeset);
        $fields_data = $fields_data_builder->getFieldsData($xml_changeset, $submitted_by);
        if (count($fields_data) > 0) {
            $artifact = $this->artifact_creator->create(
                $tracker,
                $fields_data,
                $submitted_by,
                $this->getSubmittedOn($xml_changeset),
                $this->send_notifications
            );
            if ($artifact) {
                return $artifact;
            } else {
                return $this->createFakeInitialChangeset($tracker, $xml_changeset);
            }
        }
        throw new Tracker_Artifact_Exception_EmptyChangesetException();
    }

    private function createFakeInitialChangeset(Tracker $tracker, SimpleXMLElement $xml_changeset) {
        $this->logger->warn("Impossible to create artifact with first changeset, create a fake one instead: ".$GLOBALS['Response']->getAndClearRawFeedback());
        return $this->artifact_creator->create(
            $tracker,
            array(),
            $this->getSubmittedBy($xml_changeset),
            $this->getSubmittedOn($xml_changeset),
            $this->send_notifications
        );
    }

    private function importRemainingChangesets(
        Tracker_Artifact $artifact,
        array $xml_changesets,
        Tracker_Artifact_XMLImport_ArtifactFieldsDataBuilder $fields_data_builder
    ) {
        $count = 0;
        $this->logger->info('art #'.$artifact->getId());
        foreach($xml_changesets as $xml_changeset) {
            try {
                $count++;
                $this->logger->debug("changeset $count");
                $initial_comment_body   = '';
                $initial_comment_format = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT;
                if (isset($xml_changeset->comments) && count($xml_changeset->comments->comment) > 0) {
                    $initial_comment_body   = (string)$xml_changeset->comments->comment[0]->body;
                    $initial_comment_format = (string)$xml_changeset->comments->comment[0]->body['format'];
                }
                $submitted_by = $this->getSubmittedBy($xml_changeset);
                $changeset = $this->new_changeset_creator->create(
                    $artifact,
                    $fields_data_builder->getFieldsData($xml_changeset, $submitted_by),
                    $initial_comment_body,
                    $submitted_by,
                    $this->getSubmittedOn($xml_changeset),
                    $this->send_notifications,
                    $initial_comment_format
                );
                if ($changeset) {
                    $this->updateComments($changeset, $xml_changeset);
                } else {
                    $this->logger->warn("Impossible to create changeset $count: ".$GLOBALS['Response']->getAndClearRawFeedback());
                }
            } catch (Tracker_NoChangeException $exception) {
                $this->logger->warn("No Change for changeset $count");
            } catch (Exception $exception) {
                $this->logger->warn("Unexpected error at changeset $count: ".$exception->getMessage());
            }
        }
    }

    private function updateComments(Tracker_Artifact_Changeset $changeset, SimpleXMLElement $xml_changeset) {
        if (isset($xml_changeset->comments) && count($xml_changeset->comments->comment) > 1) {
            $all_comments = $xml_changeset->comments->comment;
            for ($i = 1; $i < count($all_comments); ++$i) {
                $changeset->updateCommentWithoutNotification(
                    (string)$all_comments[$i]->body,
                    $this->getSubmittedBy($all_comments[$i]),
                    (string)$all_comments[$i]->body['format'],
                    $this->getSubmittedOn($all_comments[$i])
                );
            }
        }
    }

    private function getSubmittedBy(SimpleXMLElement $xml_changeset) {
        return $this->user_finder->getUser($xml_changeset->submitted_by);
    }

    private function getSubmittedOn(SimpleXMLElement $xml_changeset) {
        $time = strtotime((string)$xml_changeset->submitted_on);
        if ($time !== false) {
            return $time;
        }
        throw new Tracker_Artifact_Exception_XMLImportException("Invalid date format not ISO8601: ".(string)$xml_changeset->submitted_on);
    }
}
