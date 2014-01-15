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

require_once 'ArtifactXMLExporterDao.class.php';

class ArtifactXMLExporter {

    /** @var ArtifactXMLExporterDao */
    private $dao;

    public function __construct(ArtifactXMLExporterDao $dao) {
        $this->dao = $dao;
    }

    public function exportTrackerData($tracker_id, SimpleXMLElement $xml) {
        foreach ($this->dao->searchArtifacts($tracker_id) as $row) {
            $artifact_node = $xml->addChild('artifact');
            $artifact_node->addAttribute('id', $row['artifact_id']);

            $this->addAllChangesets(
                $artifact_node,
                (int)$row['artifact_id'],
                $row['summary'],
                $row['open_date'],
                $row['submitted_by']
            );
        }
    }

    private function addAllChangesets(SimpleXMLElement $xml, $artifact_id, $artifact_summary, $artifact_open_date, $artifact_submitted_by) {
        $history = $this->dao->searchSummaryHistory($artifact_id);
        if (! count($history)) {
            $this->addChangeset($xml, $artifact_submitted_by, 0, $artifact_open_date, $artifact_summary);
            return;
        }

        $this->addInitialChangeset($xml, $artifact_open_date, $history);
        foreach ($history as $row) {
            $this->addChangeset($xml, $row['submitted_by'], $row['is_anonymous'], $row['date'], $row['new_value']);
        }
        $this->addLastChangesetIfNoHistoryRecorded($xml, $artifact_summary, $row);
    }

    private function addInitialChangeset(SimpleXMLElement $xml, $submitted_on, DataAccessResult $history) {
        $row = $history->current();
        $this->addChangeset($xml, $row['submitted_by'], $row['is_anonymous'], $submitted_on, $row['old_value']);
    }

    private function addLastChangesetIfNoHistoryRecorded(SimpleXMLElement $xml, $artifact_summary, $last_history_row) {
        $last_summary = $last_history_row['new_value'];
        if ($artifact_summary !== $last_summary) {
            $this->addChangeset($xml, 'migration-tv3-to-tv5', 1, $_SERVER['REQUEST_TIME'], $artifact_summary);
        }
    }

    private function addChangeset(SimpleXMLElement $xml, $submitted_by, $is_anonymous, $submitted_on, $value) {
        $changeset_node = $xml->addChild('changeset');
        $this->setSubmittedBy($changeset_node, $submitted_by, $is_anonymous);
        $this->setSubmittedOn($changeset_node, $submitted_on);
        $this->setSummaryFieldChange($changeset_node, $value);
    }

    private function setSummaryFieldChange(SimpleXMLElement $xml, $value) {
        $field_node = $xml->addChild('field_change');
        $field_node->addAttribute('field_name', 'summary');
        $value_node = $field_node->addChild('value');
        $node = dom_import_simplexml($value_node);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($value));
    }

    private function setSubmittedBy(SimpleXMLElement $xml, $submitted_by, $is_anonymous) {
        $submitted_by_node = $xml->addChild('submitted_by', $submitted_by);
        $submitted_by_node->addAttribute('format', $is_anonymous ? 'email' : 'username');
        if ($is_anonymous) {
            $submitted_by_node->addAttribute('is_anonymous', "1");
        }
    }

    private function setSubmittedOn(SimpleXMLElement $xml, $timestamp) {
        $submitted_on_node = $xml->addChild('submitted_on', date('c', $timestamp));
        $submitted_on_node->addAttribute('format', 'ISO8601');
    }
}