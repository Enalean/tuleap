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

    /** @var DomElement */
    private $last_changeset_for_truncated_history = null;

    /** @var Array */
    private $field_initial_changeset = array(
        'attachment' => true,
        'cc'         => true,
    );

    /** @var Array */
    private $last_history_recorded = array();

    /** @var ArtifactXMLNodeHelper */
    private $node_helper;

    /** @var ArtifactFieldFactoryXMLExporter */
    private $field_factory;

    public function __construct(ArtifactXMLExporterDao $dao, ZipArchive $archive, DOMDocument $document, Logger $logger) {
        $this->dao                 = $dao;
        $this->logger              = $logger;
        $this->node_helper         = new ArtifactXMLNodeHelper($document);
        $this->attachment_exporter = new ArtifactAttachmentXMLExporter($this->node_helper, $this->dao, $archive);
        $this->field_factory       = new ArtifactFieldFactoryXMLExporter($this->dao, $this->node_helper);
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

        $current_fields_values = $this->getCurrentFieldsValues($artifact_id, $artifact_summary);
        $this->updateInitialChangesetVersusCurrentStatus($artifact_id, $current_fields_values);

        $this->addLastChangesetIfNoHistoryRecorded($artifact_node, $artifact_id, $current_fields_values);

        $this->addPermissionOnArtifactAtTheVeryEnd($artifact_node, $artifact_id);
    }

    private function getCurrentFieldsValues($artifact_id, $summary) {
        $fields_values = array(
            array(
                'field_name'   => 'summary',
                'display_type' => ArtifactStringFieldXMLExporter::TV3_DISPLAY_TYPE,
                'data_type'    => ArtifactStringFieldXMLExporter::TV3_DATA_TYPE,
                'valueText'    => $summary,
            )
        );
        foreach ($this->dao->searchFieldValues($artifact_id) as $row) {
            $fields_values[] = $row;
        }
        return $fields_values;
    }

    private function updateInitialChangesetVersusCurrentStatus($artifact_id, array $current_fields_values) {
        foreach ($current_fields_values as $field_value) {
            try {
                $this->updateInitialChangeset(
                    $artifact_id,
                    $field_value['field_name'],
                    $field_value['display_type'],
                    $field_value['data_type'],
                    $this->field_factory->getFieldValue($field_value)
                );
            } catch (Exception_TV3XMLException $exception) {
                $this->logger->warn("Artifact $artifact_id: skip update initial changeset (".$field_value['field_name']."): ".$exception->getMessage());
            }
        }
    }

    private function updateInitialChangeset($artifact_id, $field_name, $display_type, $data_type, $value) {
        if (! isset($this->field_initial_changeset[$field_name])) {
            $this->field_factory->appendValueByType(
                $this->initial_changeset,
                $artifact_id,
                array(
                    'display_type' => $display_type,
                    'data_type'    => $data_type,
                    'field_name'   => $field_name,
                    'new_value'    => $value,
                )
            );
            $this->field_initial_changeset[$field_name] = true;
        }
    }

    private function addLastChangesetIfNoHistoryRecorded(DOMElement $artifact_node, $artifact_id, array $current_fields_values) {
        foreach ($current_fields_values as $field_value) {
            try {
                $this->appendMissingValues($artifact_id, $field_value);
            } catch (Exception_TV3XMLException $exception) {
                $this->logger->warn("Artifact $artifact_id: skip fake last changeset (".$field_value['field_name']."): ".$exception->getMessage());
            }
        }
        if ($this->last_changeset_for_truncated_history) {
            $artifact_node->appendChild($this->last_changeset_for_truncated_history);
        }
    }

    private function appendMissingValues($artifact_id, $field_value) {
        if (! $this->isLastRecoredValueEqualsToCurrentValue($field_value)) {
            $this->field_factory->appendValueByType(
                $this->getLastChangesetForTruncatedHistory(),
                $artifact_id,
                array(
                    'display_type' => $field_value['display_type'],
                    'data_type'    => $field_value['data_type'],
                    'field_name'   => $field_value['field_name'],
                    'new_value'    => $this->field_factory->getFieldValue($field_value),
                )
            );
            return true;
        }
        return false;
    }

    private function getLastChangesetForTruncatedHistory() {
        if (! $this->last_changeset_for_truncated_history) {
            $this->last_changeset_for_truncated_history = $this->getBareChangeset('migration-tv3-to-tv5', 1, $_SERVER['REQUEST_TIME']);
        }
        return $this->last_changeset_for_truncated_history;
    }

    private function isLastRecoredValueEqualsToCurrentValue(array $field_value) {
        if (isset($this->last_history_recorded[$field_value['field_name']])) {
            if ($this->last_history_recorded[$field_value['field_name']] == $this->field_factory->getFieldValue($field_value)) {
                return true;
            }
            return false;
        }
        return true;
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
        $this->updateInitialChangeset($artifact_id, $row['field_name'], $row['display_type'], $row['data_type'], $row['old_value']);

        $changeset_node = $this->createOrReuseChangeset($previous_changeset, $row['submitted_by'], $row['is_anonymous'], $row['date']);

        $this->last_history_recorded[$row['field_name']] = $row['new_value'];
        $this->field_factory->appendValueByType($changeset_node, $artifact_id, $row);

        return $changeset_node;
    }

    private function addPermissionOnArtifactAtTheVeryEnd(DOMElement $artifact_node, $artifact_id) {
        $field = new ArtifactPermissionsXMLExporter($this->node_helper, $this->dao);
        $field->appendNode($artifact_node, $artifact_id);
    }
}
