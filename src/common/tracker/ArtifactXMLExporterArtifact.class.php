<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

class ArtifactXMLExporterArtifact
{

    /** @var ArtifactXMLExporterDao */
    private $dao;

    /** @var \Psr\Log\LoggerInterface */
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

    /** @var ArtifactCommentXMLExporter */
    private $comment_exporter;

    public function __construct(ArtifactXMLExporterDao $dao, ArtifactAttachmentXMLExporter $attachment_exporter, ArtifactXMLNodeHelper $node_helper, \Psr\Log\LoggerInterface $logger)
    {
        $this->dao                 = $dao;
        $this->logger              = $logger;
        $this->node_helper         = $node_helper;
        $this->attachment_exporter = $attachment_exporter;
        $this->field_factory       = new ArtifactFieldFactoryXMLExporter($this->dao, $this->node_helper);
        $this->comment_exporter    = new ArtifactCommentXMLExporter($this->node_helper);
    }

    public function exportArtifact($tracker_id, array $artifact_row)
    {
        $artifact_id   = (int) $artifact_row['artifact_id'];
        $this->logger->info("Export artifact: " . $artifact_id);
        $artifact_node = $this->node_helper->createElement('artifact');
        $artifact_node->setAttribute('id', $artifact_row['artifact_id']);

        $this->addAllChangesets(
            $artifact_node,
            $tracker_id,
            $artifact_id,
            $artifact_row
        );

        $this->attachment_exporter->addFilesToArtifact($artifact_node, $tracker_id, $artifact_id);

        return $artifact_node;
    }

    private function addAllChangesets(DOMElement $artifact_node, $tracker_id, $artifact_id, array $artifact)
    {
        $this->initial_changeset = $this->getBareChangeset($artifact['submitted_by'], 0, $artifact['open_date']);
        $artifact_node->appendChild($this->initial_changeset);

        $previous_changeset = $this->initial_changeset;
        $history = $this->dao->searchHistory($artifact_id);
        foreach ($history as $row) {
            try {
                if (! $this->comment_exporter->updateComment($row)) {
                    $node = $this->getChangeset($previous_changeset, $tracker_id, $artifact_id, $row);
                    $artifact_node->appendChild($node);
                    $previous_changeset = $node;
                }
            } catch (Exception_TV3XMLException $exception) {
                $this->logger->warning("Artifact $artifact_id: skip changeset (" . $row['field_name'] . ", " . $row['submitted_by'] . ", " . date('c', $row['date']) . "): " . $exception->getMessage());
            } catch (Exception_TV3XMLInvalidFieldTypeException $exception) {
                $this->logger->error("Artifact $artifact_id: skip changeset (" . $row['field_name'] . ", " . $row['submitted_by'] . ", " . date('c', $row['date']) . "): " . $exception->getMessage());
            }
        }

        try {
            $current_fields_values = $this->getCurrentFieldsValues($tracker_id, $artifact_id, $artifact);
            $this->updateInitialChangesetVersusCurrentStatus($tracker_id, $artifact_id, $current_fields_values);
        } catch (Exception_TV3XMLException $exception) {
            $this->logger->warning("Artifact $artifact_id: skip update of first changeset: " . $exception->getMessage());
        } catch (Exception_TV3XMLInvalidFieldTypeException $exception) {
            $this->logger->error("Artifact $artifact_id: skip update of first changeset: " . $exception->getMessage());
        }

        try {
            $this->addLastChangesetIfNoHistoryRecorded($artifact_node, $tracker_id, $artifact_id, $current_fields_values ?? []);
        } catch (Exception_TV3XMLException $exception) {
            $this->logger->warning("Artifact $artifact_id: skip last changeset if no history: " . $exception->getMessage());
        } catch (Exception_TV3XMLInvalidFieldTypeException $exception) {
            $this->logger->error("Artifact $artifact_id: skip last changeset if no history: " . $exception->getMessage());
        }

        try {
            $this->addPermissionOnArtifactAtTheVeryEnd($artifact_node, $artifact_id);
        } catch (Exception_TV3XMLException $exception) {
            $this->logger->warning("Artifact $artifact_id: skip permissions on artifact: " . $exception->getMessage());
        } catch (Exception_TV3XMLInvalidFieldTypeException $exception) {
            $this->logger->error("Artifact $artifact_id: skip permissions on artifact: " . $exception->getMessage());
        }
    }

    private function getCurrentFieldsValues($tracker_id, $artifact_id, array $artifact_row)
    {
        $fields_values = array(
            array(
                'field_name'     => 'summary',
                'display_type'   => ArtifactStringFieldXMLExporter::TV3_DISPLAY_TYPE,
                'data_type'      => ArtifactStringFieldXMLExporter::TV3_DATA_TYPE,
                'valueText'      => $artifact_row['summary'],
                'value_function' => '',
            )
        );
        if (isset($artifact_row['details'])) {
            $fields_values[] = array(
                'field_name'     => 'details',
                'display_type'   => ArtifactTextFieldXMLExporter::TV3_DISPLAY_TYPE,
                'data_type'      => ArtifactTextFieldXMLExporter::TV3_DATA_TYPE,
                'valueText'      => $artifact_row['details'],
                'value_function' => '',
            );
        }
        if (isset($artifact_row['severity']) && $artifact_row['severity']) {
            $fields_values[] = array(
                'field_name'     => 'severity',
                'display_type'   => ArtifactStaticListFieldXMLExporter::TV3_DISPLAY_TYPE,
                'data_type'      => ArtifactStaticListFieldXMLExporter::TV3_DATA_TYPE,
                'valueInt'       => $artifact_row['severity'],
                'value_function' => '',
            );
        }
        if (isset($artifact_row['close_date']) && $artifact_row['close_date']) {
            $fields_values[] = array(
                'field_name'     => 'close_date',
                'display_type'   => ArtifactDateFieldXMLExporter::TV3_DISPLAY_TYPE,
                'data_type'      => ArtifactDateFieldXMLExporter::TV3_DATA_TYPE,
                'valueDate'      => $artifact_row['close_date'],
                'value_function' => '',
            );
        }
        if (isset($artifact_row['status_id']) && $artifact_row['status_id']) {
            $fields_values[] = array(
                'field_name'     => 'status_id',
                'display_type'   => ArtifactStaticListFieldXMLExporter::TV3_DISPLAY_TYPE,
                'data_type'      => ArtifactStaticListFieldXMLExporter::TV3_DATA_TYPE,
                'valueInt'       => $artifact_row['status_id'],
                'value_function' => '',
            );
        }
        foreach ($this->dao->searchFieldValues($artifact_id) as $row) {
            $fields_values[$row['field_name']] = $this->field_factory->getCurrentFieldValue($row, $tracker_id);
        }

        return array_filter($fields_values);
    }

    private function updateInitialChangesetVersusCurrentStatus($tracker_id, $artifact_id, array $current_fields_values)
    {
        foreach ($current_fields_values as $field_value) {
            try {
                $this->updateInitialChangeset(
                    $tracker_id,
                    $artifact_id,
                    $field_value['field_name'],
                    $field_value['display_type'],
                    $field_value['data_type'],
                    $field_value['value_function'],
                    $this->field_factory->getFieldValue($field_value)
                );
            } catch (Exception_TV3XMLException $exception) {
                $this->logger->warning("Artifact $artifact_id: skip update initial changeset (" . $field_value['field_name'] . "): " . $exception->getMessage());
            }
        }
    }

    private function updateInitialChangeset($tracker_id, $artifact_id, $field_name, $display_type, $data_type, $value_function, $value)
    {
        if (! isset($this->field_initial_changeset[$field_name])) {
            $this->field_factory->appendValueByType(
                $this->initial_changeset,
                $tracker_id,
                $artifact_id,
                array(
                    'display_type'   => $display_type,
                    'data_type'      => $data_type,
                    'field_name'     => $field_name,
                    'value_function' => $value_function,
                    'new_value'      => $value,
                )
            );
            $this->field_initial_changeset[$field_name] = true;
        }
    }

    private function addLastChangesetIfNoHistoryRecorded(DOMElement $artifact_node, $tracker_id, $artifact_id, array $current_fields_values)
    {
        foreach ($current_fields_values as $field_value) {
            try {
                $this->appendMissingValues($tracker_id, $artifact_id, $field_value);
            } catch (Exception_TV3XMLException $exception) {
                $this->logger->warning("Artifact $artifact_id: skip fake last changeset (" . $field_value['field_name'] . "): " . $exception->getMessage());
            }
        }
        if ($this->last_changeset_for_truncated_history) {
            $artifact_node->appendChild($this->last_changeset_for_truncated_history);
        }
    }

    private function appendMissingValues($tracker_id, $artifact_id, $field_value)
    {
        if (! $this->isLastRecoredValueEqualsToCurrentValue($field_value)) {
            $this->field_factory->appendValueByType(
                $this->getLastChangesetForTruncatedHistory(),
                $tracker_id,
                $artifact_id,
                array(
                    'display_type'   => $field_value['display_type'],
                    'data_type'      => $field_value['data_type'],
                    'field_name'     => $field_value['field_name'],
                    'value_function' => $field_value['value_function'],
                    'new_value'      => $this->field_factory->getFieldValue($field_value),
                )
            );
            return true;
        }
        return false;
    }

    private function getLastChangesetForTruncatedHistory()
    {
        if (! $this->last_changeset_for_truncated_history) {
            $this->last_changeset_for_truncated_history = $this->getBareChangeset('migration-tv3-to-tv5', 1, $_SERVER['REQUEST_TIME']);
        }
        return $this->last_changeset_for_truncated_history;
    }

    private function isLastRecoredValueEqualsToCurrentValue(array $field_value)
    {
        if (isset($this->last_history_recorded[$field_value['field_name']])) {
            $field = $this->field_factory->getField(
                $field_value['field_name'],
                $field_value['display_type'],
                $field_value['data_type'],
                $field_value['value_function']
            );

            return $field->isValueEqual(
                $this->last_history_recorded[$field_value['field_name']],
                $this->field_factory->getFieldValue($field_value)
            );
        }
        return true;
    }

    private function getBareChangeset($submitted_by, $is_anonymous, $submitted_on)
    {
        $changeset_node = $this->node_helper->createElement('changeset');
        $this->node_helper->appendSubmittedBy($changeset_node, $submitted_by, $is_anonymous);
        $this->node_helper->appendSubmittedOn($changeset_node, $submitted_on);
        $this->comment_exporter->createRootNode($changeset_node);
        return $changeset_node;
    }

    private function createOrReuseChangeset(DOMElement $previous_changeset, $submitted_by, $is_anonymous, $submitted_on)
    {
        if ($this->isHistoryDateInReuseRange(simplexml_import_dom($previous_changeset), $submitted_on)) {
            return $previous_changeset;
        } else {
            return $this->getBareChangeset($submitted_by, $is_anonymous, $submitted_on);
        }
    }

    private function isHistoryDateInReuseRange(SimpleXMLElement $previous_changeset, $submitted_on)
    {
        return (string) $previous_changeset->submitted_on == date('c', $submitted_on);
    }

    private function getChangeset(DOMElement $previous_changeset, $tracker_id, $artifact_id, array $row)
    {
        $changeset_node = $this->createOrReuseChangeset($previous_changeset, $row['submitted_by'], $row['is_anonymous'], $row['date']);

        if ($row['field_name'] == 'comment') {
            $this->comment_exporter->appendComment($changeset_node, $row);
        } else {
            $this->updateInitialChangeset(
                $tracker_id,
                $artifact_id,
                $row['field_name'],
                $row['display_type'],
                $row['data_type'],
                $row['value_function'],
                $row['old_value']
            );

            $this->fixDataWithArtifactSpecialValues($row);

            $this->last_history_recorded[$row['field_name']] = $row['new_value'];
            $this->field_factory->appendValueByType($changeset_node, $tracker_id, $artifact_id, $row);
        }
        return $changeset_node;
    }

    private function fixDataWithArtifactSpecialValues(array &$row)
    {
        if ($this->isCloseDate($row)) {
            $row['new_value'] = $row['date'];
        }

        if ($this->isFloatFieldWithNonFloatValue($row) || $this->isIntFieldWithNonIntValue($row)) {
            $row['new_value'] = 0;
        }
    }

    private function isFloatFieldWithNonFloatValue(array $row)
    {
        if ($row['data_type'] === '3' && $row['display_type'] === 'TF') {
            return ! is_numeric($row['new_value']);
        }

        return false;
    }

    private function isIntFieldWithNonIntValue(array $row)
    {
        if ($row['data_type'] === '2' && $row['display_type'] === 'TF') {
            return ! is_numeric($row['new_value']);
        }

        return false;
    }

    private function isCloseDate(array $row)
    {
        return $row['field_name'] == 'close_date' && $this->isACloseDateHistoryEntry($row);
    }

    private function isACloseDateHistoryEntry(array $row)
    {
        return $row['old_value'] === '0' && $row['new_value'] === '';
    }

    private function addPermissionOnArtifactAtTheVeryEnd(DOMElement $artifact_node, $artifact_id)
    {
        $field = new ArtifactPermissionsXMLExporter($this->node_helper, $this->dao);
        $field->appendNode($artifact_node, $artifact_id);
    }
}
