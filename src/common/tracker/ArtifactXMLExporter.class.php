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

    const ARCHIVE_DATA_DIR = 'data';

    const XML_FILE_PREFIX = 'File';

    /** @var ArtifactXMLExporterDao */
    private $dao;

    /** @var ZipArchive */
    private $archive;

    /** @var DomDocument */
    private $document;

    /** @var Logger */
    private $logger;

    /** @var DomElement */
    private $initial_changeset;

    /** @var Array */
    private $field_initial_changeset = array();

    /** @var Array */
    private $last_history_recorded = array();

    public function __construct(ArtifactXMLExporterDao $dao, ZipArchive $archive, DOMDocument $document, Logger $logger) {
        $this->dao      = $dao;
        $this->document = $document;
        $this->logger   = $logger;
        $this->archive  = $archive;
    }

    public function exportTrackerData($tracker_id) {
        $artifacts_node = $this->document->createElement('artifacts');
        foreach ($this->dao->searchArtifacts($tracker_id) as $row) {
            $artifact_id = (int)$row['artifact_id'];
            $artifact_node = $this->document->createElement('artifact');
            $artifact_node->setAttribute('id', $row['artifact_id']);

            $this->addAllChangesets(
                $artifact_node,
                $artifact_id,
                $row['summary'],
                $row['open_date'],
                $row['submitted_by']
            );

            $this->addFilesToArtifact($artifact_node, $tracker_id, $artifact_id);

            $artifacts_node->appendChild($artifact_node);
        }
        $this->document->appendChild($artifacts_node);
    }

    private function addAllChangesets(DOMElement $artifact_node, $artifact_id, $artifact_summary, $artifact_open_date, $artifact_submitted_by) {
        $this->initial_changeset = $this->getBareChangeset( $artifact_submitted_by, 0, $artifact_open_date);
        $artifact_node->appendChild($this->initial_changeset);

        $previous_changeset = $this->initial_changeset;
        $history = $this->dao->searchHistory($artifact_id);
        foreach($history as $row) {
            try {
                $node = $this->getChangeset($previous_changeset, $artifact_id, $row['field_name'], $row['mod_by'], $row['submitted_by'], $row['is_anonymous'], $row['date'], $row['old_value'], $row['new_value']);
                $artifact_node->appendChild($node);
                $previous_changeset = $node;
            } catch (Exception_TV3XMLAttachmentNotFoundException $exception) {
                $this->logger->warn("Artifact $artifact_id: skip changeset (".$row['field_name'].", ".$row['submitted_by'].", ".date('c', $row['date'])."): ".$exception->getMessage());
            }
        }

        $this->updateInitialChangeset('summary', $artifact_summary);

        $this->addLastChangesetIfNoHistoryRecorded($artifact_node, $artifact_id, $artifact_summary);
    }

    private function updateInitialChangeset($field_name, $old_value) {
        if (! isset($this->field_initial_changeset[$field_name])) {
            if ($field_name == 'summary') {
                $this->setSummaryFieldChange($this->initial_changeset, $old_value);
            }
            $this->field_initial_changeset[$field_name] = 1;
        }
    }

    private function addLastChangesetIfNoHistoryRecorded(DOMElement $artifact_node, $artifact_id, $artifact_summary) {
        if (isset($this->last_history_recorded['summary'])) {
            if ($this->last_history_recorded['summary'] != $artifact_summary) {
                $artifact_node->appendChild($this->getChangeset($artifact_node, $artifact_id, 'summary', 0, 'migration-tv3-to-tv5', 1, $_SERVER['REQUEST_TIME'], '', $artifact_summary));
            }
        }
    }

    private function getBareChangeset($submitted_by, $is_anonymous, $submitted_on) {
        $changeset_node = $this->document->createElement('changeset');
        $this->setSubmittedBy($changeset_node, $submitted_by, $is_anonymous);
        $this->setSubmittedOn($changeset_node, $submitted_on);
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

    private function getChangeset(DOMElement $previous_changeset, $artifact_id, $field_name, $mod_by, $submitted_by, $is_anonymous, $submitted_on, $old_value, $new_value) {
        $this->updateInitialChangeset($field_name, $old_value);

        $changeset_node = $this->createOrReuseChangeset($previous_changeset, $submitted_by, $is_anonymous, $submitted_on);
        
        if ($field_name == 'summary') {
            $this->last_history_recorded['summary'] = $new_value;
            $this->setSummaryFieldChange($changeset_node, $new_value);
        } else {
            $this->setAttachmentFieldChange($changeset_node, $artifact_id, $mod_by, $submitted_on, $old_value, $new_value);
        }

        return $changeset_node;
    }

    private function setSummaryFieldChange(DOMElement $changeset_node, $value) {
        $field_node = $this->document->createElement('field_change');
        $field_node->setAttribute('field_name', 'summary');
        $field_node->appendChild($this->getNodeWithValue('value', $value));
        $changeset_node->appendChild($field_node);
    }

    private function setAttachmentFieldChange(DOMElement $changeset_node, $artifact_id, $submitted_by, $submitted_on, $old_value, $new_value) {
        $new_attachment = $this->extractNewlyCreatedAttachement($old_value, $new_value);
        if ($new_attachment) {
            $dar = $this->dao->searchFile($artifact_id, $new_attachment, $submitted_by, $submitted_on);
            if ($dar && $dar->rowCount() == 1) {
                $row_file = $dar->current();
                $field_node = $this->document->createElement('field_change');
                $field_node->setAttribute('field_name', 'attachment');
                $field_node->appendChild($this->getNodeWithValue('value', self::XML_FILE_PREFIX.$row_file['id']));
                $this->appendPreviousAttachements($field_node, $artifact_id, $submitted_on, $old_value);
                $changeset_node->appendChild($field_node);
            } else {
                throw new Exception_TV3XMLAttachmentNotFoundException('new: '.$new_attachment);
            }
        } else {
            $deleted_attachment = $this->extractNewlyCreatedAttachement($new_value, $old_value);
            throw new Exception_TV3XMLAttachmentNotFoundException('del: '.$deleted_attachment.' n:'.$new_value.' o:'.$old_value);
        }
    }

    private function appendPreviousAttachements(DOMElement $field_node, $artifact_id, $submitted_on, $old_value) {
        $previous_attachements = array_filter(explode(',', $old_value));
        foreach ($previous_attachements as $attachement) {
            $dar = $this->dao->searchFileBefore($artifact_id, $attachement, $submitted_on);
            if ($dar && $dar->rowCount() == 1) {
                $row_file = $dar->current();
                $field_node->appendChild($this->getNodeWithValue('value', 'File'.$row_file['id']));
            }
        }
    }

    /**
     * Given $old_value = 'A.png' and $new_value = 'A.png,zzz.pdf' returns 'zzz.pdf'
     *
     * Protip, before trying to refactor this with array_diff, think to the following
     * test case:
     * Given $old_value = 'A.png' and $new_value = 'A.png,A.png' returns 'A.png'
     * because 2 attachements can have same name!
     *
     * @param string $old_value
     * @param string $new_value
     */
    private function extractNewlyCreatedAttachement($old_value, $new_value) {
        $old_values_array = array_filter(explode(',', $old_value));
        $new_values_array = array_filter(explode(',', $new_value));

        for ($i = 0; $i < count($old_values_array); $i++) {
            if (isset($new_values_array[$i]) && $new_values_array[$i] == $old_values_array[$i]) {
                continue;
            } else {
                return '';
            }
        }
        if ($new_values_array[$i]) {
            return $new_values_array[$i];
        }
        return '';
    }

    private function getNodeWithValue($node_name, $value) {
        $node = $this->document->createElement($node_name);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($value));
        return $node;
    }

    private function setSubmittedBy(DOMElement $xml, $submitted_by, $is_anonymous) {
        $submitted_by_node = $this->document->createElement('submitted_by', $submitted_by);
        $submitted_by_node->setAttribute('format', $is_anonymous ? 'email' : 'username');
        if ($is_anonymous) {
            $submitted_by_node->setAttribute('is_anonymous', "1");
        }
        $xml->appendChild($submitted_by_node);
    }

    private function setSubmittedOn(DOMElement $xml, $timestamp) {
        $submitted_on_node = $this->document->createElement('submitted_on', date('c', $timestamp));
        $submitted_on_node->setAttribute('format', 'ISO8601');
        $xml->appendChild($submitted_on_node);
    }

    private function addFilesToArtifact(DOMElement $artifact_node, $artifact_type_id, $artifact_id) {
        $dar = $this->dao->searchFilesForArtifact($artifact_id);
        if (count($dar)) {
            $this->archive->addEmptyDir('data');
        }
        foreach($dar as $row) {
            $xml_file_id = self::XML_FILE_PREFIX.$row['id'];
            $this->archive->addFile(
                $this->getFilePathOnServer($artifact_type_id, $row['id']),
                $this->getFilePathInArchive($xml_file_id)
            );
            $file = $this->document->createElement('files');
            $file->appendChild($this->getNodeWithValue('id', $xml_file_id));
            $file->appendChild($this->getNodeWithValue('filename', $row['filename']));
            $file->appendChild($this->getNodeWithValue('filesize', $row['filesize']));
            $file->appendChild($this->getNodeWithValue('filetype', $row['filetype']));
            $file->appendChild($this->getNodeWithValue('description', $row['description']));
            $artifact_node->appendChild($file);
        }
    }

    private function getFilePathOnServer($artifact_type_id, $attachment_id) {
        return ArtifactFile::getPathOnFilesystemByArtifactTypeId($artifact_type_id, $attachment_id);
    }

    private function getFilePathInArchive($xml_file_id) {
        return self::ARCHIVE_DATA_DIR.DIRECTORY_SEPARATOR.'Artifact'.$xml_file_id;
    }
}
