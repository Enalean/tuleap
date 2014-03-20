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

class ArtifactXMLExporterArtifact {

    /** @var ArtifactXMLExporterDao */
    private $dao;

    /** @var Logger */
    private $logger;

    /** @var DomElement */
    private $initial_changeset;

    /** @var Array */
    private $field_initial_changeset = array();

    /** @var Array */
    private $last_history_recorded = array();

    /** @var ArtifactXMLNodeHelper */
    private $node_helper;

    /** @var Array */
    private $fields = array();

    public function __construct(ArtifactXMLExporterDao $dao, ZipArchive $archive, DOMDocument $document, Logger $logger) {
        $this->dao                 = $dao;
        $this->logger              = $logger;
        $this->node_helper         = new ArtifactXMLNodeHelper($document);
        $this->attachment_exporter = new ArtifactAttachmentXMLExporter($this->node_helper, $this->dao, $archive);
        $this->fields              = array(
            ArtifactStringFieldXMLExporter::TYPE     => new ArtifactStringFieldXMLExporter($this->node_helper),
            ArtifactAttachmentFieldXMLExporter::TYPE => new ArtifactAttachmentFieldXMLExporter($this->node_helper, $this->dao),
            ArtifactCCFieldXMLExporter::TYPE         => new ArtifactCCFieldXMLExporter($this->node_helper),
        );
    }

    public function exportArtifact($tracker_id, array $artifact_row) {
        $artifact_id   = (int)$artifact_row['artifact_id'];
        $artifact_node = $this->node_helper->createElement('artifact');
        $artifact_node->setAttribute('id', $artifact_row['artifact_id']);

        $this->addAllChangesets(
            $artifact_node,
            $artifact_id,
            $artifact_row['summary'],
            $artifact_row['open_date'],
            $artifact_row['submitted_by']
        );

        $this->attachment_exporter->addFilesToArtifact($artifact_node, $tracker_id, $artifact_id);

        return $artifact_node;
    }

    private function addAllChangesets(DOMElement $artifact_node, $artifact_id, $artifact_summary, $artifact_open_date, $artifact_submitted_by) {
        $this->initial_changeset = $this->getBareChangeset( $artifact_submitted_by, 0, $artifact_open_date);
        $artifact_node->appendChild($this->initial_changeset);

        $previous_changeset = $this->initial_changeset;
        $history = $this->dao->searchHistory($artifact_id);
        foreach($history as $row) {
            try {
                $node = $this->getChangeset($previous_changeset, $artifact_id, $row);
                $artifact_node->appendChild($node);
                $previous_changeset = $node;
            } catch (Exception_TV3XMLException $exception) {
                $this->logger->warn("Artifact $artifact_id: skip changeset (".$row['field_name'].", ".$row['submitted_by'].", ".date('c', $row['date'])."): ".$exception->getMessage());
            }
        }

        $this->updateInitialChangeset('summary', $artifact_id, $artifact_summary);

        $this->addLastChangesetIfNoHistoryRecorded($artifact_node, $artifact_id, $artifact_summary);

        $this->addPermissionOnArtifactAtTheVeryEnd($artifact_node, $artifact_id);
    }

    private function updateInitialChangeset($field_name, $artifact_id, $old_value) {
        if (! isset($this->field_initial_changeset[$field_name])) {
            if ($field_name == 'summary') {
                $field = new ArtifactStringFieldXMLExporter($this->node_helper);
                $field->appendNode($this->initial_changeset, $artifact_id, array('field_name' => 'summary', 'new_value' => $old_value));
            }
            $this->field_initial_changeset[$field_name] = 1;
        }
    }

    private function addLastChangesetIfNoHistoryRecorded(DOMElement $artifact_node, $artifact_id, $artifact_summary) {
        if (isset($this->last_history_recorded['summary'])) {
            if ($this->last_history_recorded['summary'] != $artifact_summary) {
                $artifact_node->appendChild(
                    $this->getChangeset(
                        $artifact_node,
                        $artifact_id,
                        array(
                            'data_type'    => '1',
                            'display_type' => 'TF',
                            'field_name'   => 'summary',
                            'mod_by'       => 0,
                            'submitted_by' => 'migration-tv3-to-tv5',
                            'is_anonymous' => 1,
                            'date'         => $_SERVER['REQUEST_TIME'],
                            'old_value'    => '',
                            'new_value'    => $artifact_summary,
                        )
                    )
                );
            }
        }
    }

    private function getBareChangeset($submitted_by, $is_anonymous, $submitted_on) {
        $changeset_node = $this->node_helper->createElement('changeset');
        $this->node_helper->appendSubmittedBy($changeset_node, $submitted_by, $is_anonymous);
        $this->node_helper->appendSubmittedOn($changeset_node, $submitted_on);
        return $changeset_node;
    }

    private function createOrReuseChangeset(DOMElement $previous_changeset, $submitted_by, $is_anonymous, $submitted_on) {
        if ($this->isHistoryDateInReuseRange(simplexml_import_dom($previous_changeset), $submitted_on)) {
            return $previous_changeset;
        } else {
            return $this->getBareChangeset($submitted_by, $is_anonymous, $submitted_on);
        }
    }

    private function isHistoryDateInReuseRange(SimpleXMLElement $previous_changeset, $submitted_on) {
        return (string)$previous_changeset->submitted_on == date('c', $submitted_on);
    }

    private function getChangeset(DOMElement $previous_changeset, $artifact_id, array $row) {
        $this->updateInitialChangeset($row['field_name'], $artifact_id, $row['old_value']);

        $changeset_node = $this->createOrReuseChangeset($previous_changeset, $row['submitted_by'], $row['is_anonymous'], $row['date']);

        $this->last_history_recorded[$row['field_name']] = $row['new_value'];
        $this->fields[$this->getFieldType($row)]->appendNode($changeset_node, $artifact_id, $row);

        return $changeset_node;
    }

    private function getFieldType(array $row) {
        switch ($row['display_type']) {
            case 'TF':
                return ArtifactStringFieldXMLExporter::TYPE;

            case null:
                if (isset($this->fields[$row['field_name']])) {
                    return $row['field_name'];
                }

            default:
                throw new Exception_TV3XMLUnknownFieldTypeException($row['field_name']);
        }
    }

    private function addPermissionOnArtifactAtTheVeryEnd(DOMElement $artifact_node, $artifact_id) {
        $field = new ArtifactPermissionsXMLExporter($this->node_helper, $this->dao);
        $field->appendNode($artifact_node, $artifact_id);
    }
}
