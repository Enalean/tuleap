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

require_once 'common/tracker/ArtifactXMLExporter.class.php';

class ArtifactXMLExporter_SummaryTest extends TuleapTestCase {

    /** @var ArtifactXMLExporter */
    private $exporter;
    /** @var ArtifactXMLExporterDao */
    private $dao;
    /** @var SimpleXMLElement */
    private $xml;
    private $tracker_id = 1;
    private $fixtures_dir;
    private $open_date = 1234567890; // the same as in fixtures
    private $expected_open_date;

    public function setUp() {
        parent::setUp();
        $this->dao                = mock('ArtifactXMLExporterDao');
        $this->exporter           = new ArtifactXMLExporter($this->dao);
        $this->xml                = new SimpleXMLElement('<artifacts />');
        $this->fixtures_dir       = dirname(__FILE__) .'/_fixtures/';
        $this->expected_open_date = $this->toExpectedDate($this->open_date);
    }

    public function itCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory() {
        $this->exportTrackerDataFromFixture('artifact_without_any_history');

        $this->assertEqual((string)$this->xml->artifact->changeset[0]->field_change->value, 'Le artifact without history');
        $this->assertEqual((string)$this->xml->artifact->changeset[0]->submitted_on, $this->expected_open_date);
    }

    public function itCreatesAnInitialChangesetBasedOnTheOldestValueKnownWhenThereIsHistory() {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        $this->assertEqual((string)$this->xml->artifact->changeset[0]->field_change->value, 'Le artifact');
        $this->assertEqual((string)$this->xml->artifact->changeset[0]->submitted_on, $this->expected_open_date);
    }

    public function itCreatesAChangesetForEachHistoryEntry() {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        $this->assertEqual((string)$this->xml->artifact->changeset[1]->field_change->value, 'Le artifact with history');
        $this->assertEqual((string)$this->xml->artifact->changeset[1]->submitted_on, $this->toExpectedDate(2234567890));
        $this->assertEqual((string)$this->xml->artifact->changeset[2]->field_change->value, 'Le artifact with full history');
        $this->assertEqual((string)$this->xml->artifact->changeset[2]->submitted_on, $this->toExpectedDate(3234567890));
    }

    public function itCreatesALastChangesetAtImportTimeWhenHistoryDiffersFromCurrentState() {
        $this->exportTrackerDataFromFixture('artifact_with_half_history');

        $this->assertEqual((string)$this->xml->artifact->changeset[3]->field_change->value, 'Le artifact with half history');
        $this->assertEqual((string)$this->xml->artifact->changeset[3]->submitted_on, $this->toExpectedDate($_SERVER['REQUEST_TIME']));
    }

    private function exportTrackerDataFromFixture($fixture) {
        $this->loadFixtures($fixture);
        $this->exporter->exportTrackerData($this->tracker_id, $this->xml);
    }

    private function loadFixtures($fixture) {
        $fixture_content = file_get_contents($this->fixtures_dir . $fixture .'.json');
        $json = $this->decodeJson($fixture_content);

        foreach ($json['artifact'] as $tracker_id => $artifact_rows) {
            stub($this->dao)->searchArtifacts($tracker_id)->returnsDarFromArray($artifact_rows);
        }
        foreach ($json['artifact_history'] as $artifact_id => $history_rows) {
            stub($this->dao)->searchSummaryHistory($artifact_id)->returnsDarFromArray($history_rows);
        }
    }

    private function toExpectedDate($timestamp) {
        return date('c', $timestamp);
    }

    private function decodeJson($string) {
        $json = json_decode($string, true);

        $json_error = json_last_error();
        if ($json_error !== JSON_ERROR_NONE) {
            $this->throwJsonError($json_error);
        }

        return $json;
    }

    private function throwJsonError($json_error) {
        $msg = '';
        switch ($json_error) {
            case JSON_ERROR_NONE:
                $msg = 'No errors';
            break;
            case JSON_ERROR_DEPTH:
                $msg = 'Maximum stack depth exceeded';
            break;
            case JSON_ERROR_STATE_MISMATCH:
                $msg = 'Underflow or the modes mismatch';
            break;
            case JSON_ERROR_CTRL_CHAR:
                $msg = 'Unexpected control character found';
            break;
            case JSON_ERROR_SYNTAX:
                $msg = 'Syntax error, malformed JSON';
            break;
            case JSON_ERROR_UTF8:
                $msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
            default:
                $msg = 'Unknown error';
            break;
        }

        throw new Exception($msg);
    }
}
