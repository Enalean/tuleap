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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ArtifactXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\TemporaryTestDirectory;

    /** @var ArtifactXMLExporter */
    private $exporter;
    /** @var ArtifactXMLExporterDao */
    private $dao;
    /** @var SimpleXMLElement */
    private $xml;
    /** @var DomDocument */
    private $dom;
    private $tracker_id = 1;
    private $fixtures_dir;
    private $open_date = 1234567890; // the same as in fixtures
    private $expected_open_date;
    private $archive;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao     = $this->createMock(\ArtifactXMLExporterDao::class);
        $this->dom     = new DOMDocument("1.0", "UTF8");
        $this->archive = new ZipArchive();
        $this->archive->open($this->getTmpDir() . '/a', ZipArchive::CREATE);
        $node_helper              = new ArtifactXMLNodeHelper($this->dom);
        $attachment_exporter      = new ArtifactAttachmentXMLZipper($node_helper, $this->dao, $this->archive, true);
        $this->logger             = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->exporter           = new ArtifactXMLExporter($this->dao, $attachment_exporter, $node_helper, $this->logger);
        $this->fixtures_dir       = __DIR__ . '/_fixtures/';
        $this->expected_open_date = $this->toExpectedDate($this->open_date);
        ForgeConfig::store();
        ForgeConfig::set('sys_data_dir', dirname($this->fixtures_dir));

        $this->logger->method('info');
    }

    protected function tearDown(): void
    {
        $this->archive->close();
    }

    protected function toExpectedDate(int $timestamp): string
    {
        return date('c', $timestamp);
    }

    private function exportTrackerDataFromFixture(string $fixture): void
    {
        $this->loadFixtures($fixture);
        $this->exporter->exportTrackerData($this->tracker_id);
        $this->xml = simplexml_import_dom($this->dom);
    }

    private function loadFixtures($fixture)
    {
        $file_path       = $this->fixtures_dir . $fixture . '.json';
        $fixture_content = file_get_contents($file_path);
        if ($fixture_content == false) {
            throw new Exception("Unable to load $file_path (mis-typed?)");
        }
        $json = json_decode($fixture_content, true, 512, JSON_THROW_ON_ERROR);

        $expected_results_map = [];
        foreach ($json['artifact'] as $tracker_id => $artifact_rows) {
            $expected_results_map[] = [$tracker_id, \TestHelper::argListToDar($artifact_rows)];
        }
        $this->dao->method('searchArtifacts')->willReturnMap($expected_results_map);

        $expected_results_map = [];
        foreach ($json['artifact_history'] as $artifact_id => $history_rows) {
            $expected_results_map[] = [$artifact_id, \TestHelper::argListToDar($history_rows)];
        }
        $this->dao->method('searchHistory')->willReturnMap($expected_results_map);

        if (isset($json['artifact_file']) && $json['artifact_file'] !== null) {
            $expected_results_map = [];
            foreach ($json['artifact_file'] as $artifact_id => $file_rows) {
                $expected_results_map[] = [$artifact_id, \TestHelper::argListToDar($file_rows)];
            }
            $this->dao->method('searchFilesForArtifact')->willReturnMap($expected_results_map);
        } else {
            $this->dao->method('searchFilesForArtifact')->with($artifact_id)->willReturn(\TestHelper::emptyDar());
        }
        if (isset($json['artifact_file_search']) && $json['artifact_file_search'] !== null) {
            $expected_results_map = [];
            foreach ($json['artifact_file_search'] as $artifact_file) {
                $params                 = $artifact_file['parameters'];
                $expected_results_map[] = [
                    $params['artifact_id'],
                    $params['filename'],
                    $params['submitted_by'],
                    $params['date'],
                    \TestHelper::argListToDar($artifact_file['rows']),
                ];
            }
            $this->dao->method('searchFile')->willReturnMap($expected_results_map);
        } else {
            $expected_results_map = [];
            foreach ($json['artifact'] as $artifact_data) {
                $expected_results_map[] = [$artifact_data[0]['artifact_id'], \TestHelper::emptyDar()];
            }
            $this->dao->method('searchFile')->willReturnMap($expected_results_map);
        }
        if (isset($json['search_file_before']) && $json['search_file_before'] !== null) {
            $expected_results_map = [];
            foreach ($json['search_file_before'] as $artifact_file) {
                $params                 = $artifact_file['parameters'];
                $expected_results_map[] = [
                    $params['artifact_id'],
                    $params['filename'],
                    $params['date'],
                    \TestHelper::argListToDar($artifact_file['rows']),
                ];
            }
            $this->dao->method('searchFileBefore')->willReturnMap($expected_results_map);
        } else {
            $expected_results_map = [];
            foreach ($json['artifact'] as $artifact_data) {
                $expected_results_map[] = [$artifact_data[0]['artifact_id'], \TestHelper::emptyDar()];
            }
            $this->dao->method('searchFileBefore')->willReturnMap($expected_results_map);
        }
        if (isset($json['search_cc_at']) && $json['search_cc_at'] !== null) {
            $expected_results_map = [];
            foreach ($json['search_cc_at'] as $artifact_cc) {
                $params                 = $artifact_cc['parameters'];
                $expected_results_map[] = [
                    $params['artifact_id'],
                    $params['submitted_by'],
                    $params['date'],
                    \TestHelper::argListToDar($artifact_cc['rows']),
                ];
            }
            $this->dao->method('searchCCAt')->willReturnMap($expected_results_map);
        } else {
            $expected_results_map = [];
            foreach ($json['artifact'] as $artifact_data) {
                $expected_results_map[] = [$artifact_data[0]['artifact_id'], \TestHelper::emptyDar()];
            }
            $this->dao->method('searchCCAt')->willReturnMap($expected_results_map);
        }

        if (isset($json['permissions']) && $json['permissions'] !== null) {
            $expected_results_map = [];
            foreach ($json['permissions'] as $artifact_id => $perms) {
                $expected_results_map[] = [$artifact_id, \TestHelper::argListToDar($perms)];
            }
            $this->dao->method('searchPermsForArtifact')->with($artifact_id)->willReturnMap($expected_results_map);
        } else {
            $this->dao->method('searchPermsForArtifact')->willReturn(\TestHelper::emptyDar());
        }

        if (isset($json['artifact_field_value']) && $json['artifact_field_value'] !== null) {
            $expected_results_map = [];
            foreach ($json['artifact_field_value'] as $artifact_id => $history_rows) {
                $expected_results_map[] = [$artifact_id, \TestHelper::argListToDar($history_rows)];
            }
            $this->dao->method('searchFieldValues')->willReturnMap($expected_results_map);
        } else {
            $this->dao->method('searchFieldValues')->willReturn(\TestHelper::emptyDar());
        }

        if (isset($json['artifact_field_value_list']) && $json['artifact_field_value_list'] !== null) {
            $expected_results_map = [];
            foreach ($json['artifact_field_value_list'] as $artifact_field_value_list) {
                $params                 = $artifact_field_value_list['parameters'];
                $expected_results_map[] = [
                    $params['group_artifact_id'],
                    $params['field_name'],
                    \TestHelper::argListToDar($artifact_field_value_list['rows']),
                ];
            }
            $this->dao->method('searchFieldValuesList')->willReturnMap($expected_results_map);
        } else {
            $expected_results_map = [];
            foreach ($json['artifact'] as $artifact_data) {
                $expected_results_map[] = [$artifact_data[0]['artifact_id'], \TestHelper::emptyDar()];
            }
            $this->dao->method('searchFieldValuesList')->willReturnMap($expected_results_map);
        }

        if (isset($json['user']) && $json['user'] !== null) {
            $all_users            = [];
            $expected_results_map = [];
            foreach ($json['user'] as $user_id => $user_rows) {
                $expected_results_map[] = ["$user_id", \TestHelper::argListToDar($user_rows)];
                $all_users[]            =  [
                    'user_id'   => $user_id,
                    'user_name' => $user_rows[0]['user_name'],
                ];
            }
            $this->dao->method('searchUser')->willReturnMap($expected_results_map);
            $this->dao->method('getAllUsers')->willReturn(\TestHelper::argListToDar($all_users));
        } else {
            $this->dao->method('searchUser')->willReturn(\TestHelper::emptyDar());
            $this->dao->method('getAllUsers')->willReturn(\TestHelper::emptyDar());
        }
    }

    private function findValue(SimpleXMLElement $field_change, $name)
    {
        foreach ($field_change as $change) {
            if ($change['field_name'] == $name) {
                return $change;
            }
        }
        throw new Exception("$name not found");
    }

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_without_any_history');

        self::assertEquals('Le artifact without history', (string) $this->xml->artifact->changeset[0]->field_change[0]->value);
        self::assertEquals('string', (string) $this->xml->artifact->changeset[0]->field_change[0]['type']);
        self::assertEquals('summary', (string) $this->xml->artifact->changeset[0]->field_change[0]['field_name']);
        self::assertEquals('Le original submission', (string) $this->xml->artifact->changeset[0]->field_change[1]->value);
        self::assertEquals('text', (string) $this->xml->artifact->changeset[0]->field_change[1]['type']);
        self::assertEquals('details', (string) $this->xml->artifact->changeset[0]->field_change[1]['field_name']);
        self::assertEquals('1 - Ordinary', (string) $this->xml->artifact->changeset[0]->field_change[2]->value);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[0]->field_change[2]['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[0]->field_change[2]['bind']);
        self::assertEquals('severity', (string) $this->xml->artifact->changeset[0]->field_change[2]['field_name']);
        self::assertEquals($this->expected_open_date, (string) $this->xml->artifact->changeset[0]->submitted_on);
    }

    public function testItCreatesAnInitialChangesetBasedOnTheOldestValueKnownWhenThereIsHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        self::assertEquals('Le artifact', (string) $this->xml->artifact->changeset[0]->field_change[0]->value);
        self::assertEquals('Le original submission that will be updated', (string) $this->xml->artifact->changeset[0]->field_change[1]->value);
        self::assertEquals('Le original submission', (string) $this->xml->artifact->changeset[2]->field_change[1]->value);
        self::assertEquals($this->expected_open_date, (string) $this->xml->artifact->changeset[0]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntry(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        self::assertEquals('Le artifact with history', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals($this->toExpectedDate(2234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);
        self::assertEquals('Le artifact with full history', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesALastChangesetAtImportTimeWhenHistoryDiffersFromCurrentState(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_half_history');

        self::assertEquals('Le artifact with half history', (string) $this->xml->artifact->changeset[3]->field_change->value);
        self::assertEquals($this->toExpectedDate($_SERVER['REQUEST_TIME']), (string) $this->xml->artifact->changeset[3]->submitted_on);
    }

    public function testItDoesntMessPreviousArtifactWhenTryingToUpdateInitialChangeset(): void
    {
        $this->exportTrackerDataFromFixture('two_artifacts');

        self::assertCount(2, $this->xml->artifact);

        self::assertEquals('Le artifact with full history', (string) $this->xml->artifact[0]->changeset[0]->field_change->value);
        self::assertEquals('Le artifact', (string) $this->xml->artifact[1]->changeset[0]->field_change->value);
        self::assertEquals('The second one', (string) $this->xml->artifact[1]->changeset[1]->field_change->value);
    }

    public function testItHasChangesetPerComment(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_comment');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        self::assertEquals('This is my comment', (string) $this->xml->artifact->changeset[1]->comments->comment->body);
        self::assertEquals('text', (string) $this->xml->artifact->changeset[1]->comments->comment->body['format']);

        self::assertEquals($this->toExpectedDate(1234569000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        self::assertEquals('<p>With<strong> CHTEUMEULEU</strong></p>', (string) $this->xml->artifact->changeset[2]->comments->comment->body);
        self::assertEquals('html', (string) $this->xml->artifact->changeset[2]->comments->comment->body['format']);
    }

    public function testItHasACommentVersions(): void
    {
        $this->logger->expects(self::never())->method('warning');
        $this->exportTrackerDataFromFixture('artifact_with_comment_updates');
        self::assertCount(2, $this->xml->artifact->changeset);
        self::assertCount(3, $this->xml->artifact->changeset[1]->comments->comment);

        self::assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $comments = $this->xml->artifact->changeset[1]->comments;

        self::assertEquals($this->toExpectedDate(1234568000), (string) $comments->comment[0]->submitted_on);

        self::assertEquals('This is my comment', (string) $comments->comment[0]->body);
        self::assertEquals('text', (string) $comments->comment[0]->body['format']);

        self::assertEquals($this->toExpectedDate(1234569000), (string) $comments->comment[1]->submitted_on);
        self::assertEquals('goofy', (string) $comments->comment[1]->submitted_by);
        self::assertEquals('<p>With<strong> CHTEUMEULEU</strong></p>', (string) $comments->comment[1]->body);
        self::assertEquals('html', (string) $comments->comment[1]->body['format']);

        self::assertEquals($this->toExpectedDate(1234569500), (string) $comments->comment[2]->submitted_on);
        self::assertEquals('goofy', (string) $comments->comment[2]->submitted_by);
        self::assertEquals('<p>With<strong> HTML</strong></p>', (string) $comments->comment[2]->body);
        self::assertEquals('html', (string) $comments->comment[2]->body['format']);
    }

    public function testItCreatesAChangesetWithOneAttachment(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_one_attachment');
        self::assertCount(2, $this->xml->artifact->changeset);

        self::assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('file', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('File30', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);
        self::assertEquals($this->toExpectedDate(3234567900), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertCount(1, $this->xml->artifact->file);
        self::assertEquals('File30', (string) $this->xml->artifact->file[0]['id']);
        self::assertEquals('A.png', (string) $this->xml->artifact->file[0]->filename);
        self::assertEquals(12323, (int) $this->xml->artifact->file[0]->filesize);
        self::assertEquals('image/png', (string) $this->xml->artifact->file[0]->filetype);
        self::assertEquals('The screenshot', (string) $this->xml->artifact->file[0]->description);
        self::assertEquals(2, $this->archive->numFiles);
    }

    public function testItCreatesAChangesetWithTwoAttachmentsWithSameName(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_two_attachments_same_name');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('File30', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);

        self::assertEquals('attachment', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertCount(2, $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals('File31', (string) $this->xml->artifact->changeset[2]->field_change->value[0]['ref']);
        self::assertEquals('File30', (string) $this->xml->artifact->changeset[2]->field_change->value[1]['ref']);
        self::assertEquals($this->toExpectedDate(3234568000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        self::assertCount(2, $this->xml->artifact->file);
        self::assertEquals('File30', (string) $this->xml->artifact->file[0]['id']);
        self::assertEquals('A.png', (string) $this->xml->artifact->file[0]->filename);
        self::assertEquals(12323, (int) $this->xml->artifact->file[0]->filesize);
        self::assertEquals('image/png', (string) $this->xml->artifact->file[0]->filetype);
        self::assertEquals('The screenshot', (string) $this->xml->artifact->file[0]->description);

        self::assertEquals('File31', (string) $this->xml->artifact->file[1]['id']);
        self::assertEquals('A.png', (string) $this->xml->artifact->file[1]->filename);
        self::assertEquals(50, (int) $this->xml->artifact->file[1]->filesize);
        self::assertEquals('image/png', (string) $this->xml->artifact->file[1]->filetype);
        self::assertEquals('The screenshot v2', (string) $this->xml->artifact->file[1]->description);
    }

    public function testItCreatesAChangesetWithDeletedAttachments(): void
    {
        $this->logger->method('warning');

        $this->exportTrackerDataFromFixture('artifact_with_deleted_attachment');

        self::assertCount(2, $this->xml->artifact->changeset);

        self::assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertCount(1, $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('File31', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);
        self::assertEquals($this->toExpectedDate(3234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertCount(1, $this->xml->artifact->file);
        self::assertEquals('File31', (string) $this->xml->artifact->file[0]['id']);
        self::assertEquals('zzz.pdf', (string) $this->xml->artifact->file[0]->filename);
    }

    public function testItCreatesAChangesetWithNullAttachments(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_null_attachment');

        self::assertCount(1, $this->xml->artifact->changeset);
        foreach ($this->xml->artifact->changeset->field_change as $change) {
            self::assertNotEquals('attachment', (string) $change['field_name']);
        }
    }

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoCCChanges(): void
    {
        $this->exportTrackerDataFromFixture('artifact_cc_no_changes');

        self::assertCount(2, $this->xml->artifact->changeset[0]->field_change);
        self::assertChangesItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory($this->xml->artifact->changeset[0]->field_change[0]);
        self::assertChangesItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory($this->xml->artifact->changeset[0]->field_change[1]);
    }

    private function assertChangesItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory(SimpleXMLElement $field_change): void
    {
        switch ($field_change['field_name']) {
            case 'cc':
                self::assertEquals('open_list', (string) $field_change['type']);
                self::assertEquals('users', (string) $field_change['bind']);
                self::assertEquals('john@doe.org', (string) $field_change->value[0]);
                self::assertEquals('jeanjean', (string) $field_change->value[1]);

                self::assertFalse(isset($field_change->value[0]['format']));
                self::assertFalse(isset($field_change->value[1]['format']));
                break;
            case 'summary':
                // Ok but we don't care
                break;
            default:
                throw new Exception('Unexpected field type: ' . $field_change['field_name']);
                break;
        }
    }

    public function testItCreatesTheTwoCCChangesChangeset(): void
    {
        $this->exportTrackerDataFromFixture('artifact_cc_add_new');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('jeanjean', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
    }

    public function testItCreatesChangesWithDeletedCC(): void
    {
        $this->exportTrackerDataFromFixture('artifact_cc_remove');

        self::assertCount(2, $this->xml->artifact->changeset);

        self::assertCount(3, $this->xml->artifact->changeset[0]->field_change->value);
        self::assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[0]->field_change->value[0]);
        self::assertEquals('jeanjean', (string) $this->xml->artifact->changeset[0]->field_change->value[1]);
        self::assertEquals('bla@bla.org', (string) $this->xml->artifact->changeset[0]->field_change->value[2]);

        self::assertCount(1, $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[1]->field_change->value);
    }

    public function testItSetNoneAsOriginalSeverityValue(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_severity_history');

        self::assertEquals('1 - Ordinary', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals('severity', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
    }

    public function testItCreatesASingleChangesetWithSummaryAndAttachment(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_summary_and_attachment');
        self::assertCount(1, $this->xml->artifact->changeset);

        self::assertEquals($this->toExpectedDate(1234567890), (string) $this->xml->artifact->changeset[0]->submitted_on);

        // cannot guarranty the order of execution therefore specific assertion in dedicated method
        self::assertCount(2, $this->xml->artifact->changeset[0]->field_change);
        self::assertChangesItCreatesASingleChangesetWithSummaryAndAttachment($this->xml->artifact->changeset[0]->field_change[0]);
        self::assertChangesItCreatesASingleChangesetWithSummaryAndAttachment($this->xml->artifact->changeset[0]->field_change[1]);

        self::assertCount(1, $this->xml->artifact->file);
    }

    private function assertChangesItCreatesASingleChangesetWithSummaryAndAttachment(SimpleXMLElement $field_change)
    {
        switch ($field_change['field_name']) {
            case 'attachment':
                self::assertEquals('File30', $field_change->value[0]['ref']);
                break;
            case 'summary':
                self::assertEquals('Le artifact with full history', $field_change->value);
                break;
            default:
                throw new Exception('Unexpected field type: ' . $field_change['field_name']);
                break;
        }
    }

    public function testItCreatesChangesetWithSummaryAndAttachmentChange(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_summary_and_attachment_change');

        self::assertCount(3, $this->xml->artifact->changeset);

        // Changeset1: original summary
        self::assertEquals($this->toExpectedDate(1234567890), (string) $this->xml->artifact->changeset[0]->submitted_on);
        self::assertEquals('summary', (string) $this->xml->artifact->changeset[0]->field_change['field_name']);
        self::assertEquals('Le artifact with full history', (string) $this->xml->artifact->changeset[0]->field_change->value);

        // Changeset2: attachment
        self::assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        self::assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('File30', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);

        // Changeset3: new summary
        self::assertEquals($this->toExpectedDate(1234569000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        self::assertEquals('summary', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('Le artifact with updated summary', (string) $this->xml->artifact->changeset[2]->field_change->value);

        self::assertCount(1, $this->xml->artifact->file);
    }

    public function testItCreatesChangesetWithAttachmentAndSummaryWhenHistoryDiffersFromCurrentState(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_summary_attachment_half_history');

        self::assertCount(4, $this->xml->artifact->changeset);

        // Changeset1: original summary
        self::assertEquals($this->toExpectedDate(1234567890), (string) $this->xml->artifact->changeset[0]->submitted_on);
        self::assertEquals('summary', (string) $this->xml->artifact->changeset[0]->field_change['field_name']);
        self::assertEquals('Le artifact with full history', (string) $this->xml->artifact->changeset[0]->field_change->value);

        // Changeset2: new summary
        self::assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        self::assertEquals('summary', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('Le artifact with updated summary', (string) $this->xml->artifact->changeset[1]->field_change->value);

        // Changeset3: attachment
        self::assertEquals($this->toExpectedDate(1234569000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        self::assertEquals('attachment', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('File30', (string) $this->xml->artifact->changeset[2]->field_change->value[0]['ref']);

        // Changeset4: last summary update
        self::assertEquals($this->toExpectedDate($_SERVER['REQUEST_TIME']), (string) $this->xml->artifact->changeset[3]->submitted_on);
        self::assertEquals('summary', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        self::assertEquals('Le artifact with half history', (string) $this->xml->artifact->changeset[3]->field_change->value);

        self::assertCount(1, $this->xml->artifact->file);
    }

    public function testItDoesNotExportPermsIfThereIsNoPerms(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');
        foreach ($this->xml->artifact->xpath('changeset') as $changeset) {
            self::assertThereIsNoPermissionsFieldChange($changeset);
        }
    }

    public function testItCreatesPermsOnArtifactAtTheVeryEnd(): void
    {
        $permissions_are_exported = false;
        $this->exportTrackerDataFromFixture('artifact_with_full_history_with_perms_on_artifact');

        $nb_of_changesets = count($this->xml->artifact->changeset);
        $last_changeset   = $this->xml->artifact->changeset[$nb_of_changesets - 1];

        foreach ($last_changeset->field_change as $field_change) {
            if ((string) $field_change['field_name'] !== 'permissions_on_artifact') {
                continue;
            }
            self::assertEquals('permissions_on_artifact', (string) $field_change['type']);
            self::assertEquals('1', (string) $field_change['use_perm']);
            self::assertCount(2, $field_change->ugroup);
            self::assertEquals('15', (string) $field_change->ugroup[0]['ugroup_id']);
            self::assertEquals('101', (string) $field_change->ugroup[1]['ugroup_id']);
            $permissions_are_exported = true;
        }

        self::assertTrue($permissions_are_exported);
    }

    public function testItTransformsNobodyIntoProjectAdministrators(): void
    {
        $permissions_are_exported = false;
        $this->exportTrackerDataFromFixture('artifact_with_full_history_with_perms_on_artifact_with_nobody');

        $nb_of_changesets = count($this->xml->artifact->changeset);
        $last_changeset   = $this->xml->artifact->changeset[$nb_of_changesets - 1];

        foreach ($last_changeset->field_change as $field_change) {
            if ((string) $field_change['field_name'] !== 'permissions_on_artifact') {
                continue;
            }
            self::assertEquals('permissions_on_artifact', (string) $field_change['type']);
            self::assertEquals('1', (string) $field_change['use_perm']);
            self::assertCount(1, $field_change->ugroup);
            self::assertEquals('4', (string) $field_change->ugroup[0]['ugroup_id']);
            $permissions_are_exported = true;
        }

        self::assertTrue($permissions_are_exported);
    }

    public function testItDoesNotExportPermissionsInFirstChangesets(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        $first_changesets = array_slice($this->xml->artifact->xpath('changeset'), 0, -1);
        foreach ($first_changesets as $changeset) {
            self::assertThereIsNoPermissionsFieldChange($changeset);
        }
    }

    private function assertThereIsNoPermissionsFieldChange(SimpleXMLElement $changeset): void
    {
        foreach ($changeset->field_change as $field_change) {
            self::assertNotEquals('permissions_on_artifact', (string) $field_change['field_name']);
        }
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithStringHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_string_history');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('The error code is 23232', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('field_14', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('string', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('The error code is not returned', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals('field_14', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('string', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistoryWithString(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_string_no_history');

        self::assertCount(1, $this->xml->artifact->changeset);

        self::assertCount(2, $this->xml->artifact->changeset[0]->field_change);
        self::assertChangesItCreatesASingleChangesetWithSummaryAndString($this->xml->artifact->changeset[0]->field_change[0]);
        self::assertChangesItCreatesASingleChangesetWithSummaryAndString($this->xml->artifact->changeset[0]->field_change[1]);
    }

    private function assertChangesItCreatesASingleChangesetWithSummaryAndString(SimpleXMLElement $field_change): void
    {
        switch ($field_change['field_name']) {
            case 'field_14':
                self::assertEquals('The error code is not returned', $field_change->value);
                break;
            case 'summary':
                self::assertEquals('Le artifact with full history', $field_change->value);
                break;
            default:
                throw new Exception('Unexpected field type: ' . $field_change['field_name']);
                break;
        }
    }

    public function testItDoesntCreateAnExtraChangesetWhenThereIsAFloatToStringConversionWithTrailingZero(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_float_history');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('66.98', (string) $this->xml->artifact->changeset[1]->field_change->value);

        self::assertEquals('2048', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItReturnsZeroIfNoNewValue(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_float_history_with_no_value');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('66.98', (string) $this->xml->artifact->changeset[1]->field_change->value);

        self::assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItConvertsHistoricalValuesWhenFieldTypeChanged(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_float_history_with_string_value');
        self::assertCount(4, $this->xml->artifact->changeset);

        self::assertEquals('0', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('2048', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals('43.0', (string) $this->xml->artifact->changeset[3]->field_change->value);
    }

    public function testItDoesntCreateAnExtraChangesetWhenThereIsAnIntToStringConversionWithTrailingZero(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_integer_history');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('66', (string) $this->xml->artifact->changeset[1]->field_change->value);

        self::assertEquals('2048', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItReturnsZeroIfNoNewValueWithIntegerHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_integer_history_with_no_value');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('66', (string) $this->xml->artifact->changeset[1]->field_change->value);

        self::assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItConvertsHistoricalValuesWhenFieldTypeChangedWithIntegerAndStringHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_integer_history_with_string_value');
        self::assertCount(5, $this->xml->artifact->changeset);

        self::assertEquals('0', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals('4', (string) $this->xml->artifact->changeset[3]->field_change->value);
        self::assertEquals('43', (string) $this->xml->artifact->changeset[4]->field_change->value);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithScalarHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_scalar_history');

        self::assertCount(6, $this->xml->artifact->changeset);

        self::assertCount(6, $this->xml->artifact->changeset[0]->field_change);
        self::assertEquals('', (string) $this->findValue($this->xml->artifact->changeset[0]->field_change, 'field_18')->value);

        self::assertEquals('The error code is 23232', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('field_14', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('string', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals($this->toExpectedDate(3234567100), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals("some text", (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals('field_15', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('text', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals($this->toExpectedDate(3234567200), (string) $this->xml->artifact->changeset[2]->submitted_on);

        self::assertEquals("9001", (string) $this->xml->artifact->changeset[3]->field_change->value);
        self::assertEquals('field_16', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        self::assertEquals('int', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        self::assertEquals($this->toExpectedDate(3234567300), (string) $this->xml->artifact->changeset[3]->submitted_on);

        self::assertEquals("66.98", (string) $this->xml->artifact->changeset[4]->field_change->value);
        self::assertEquals('field_17', (string) $this->xml->artifact->changeset[4]->field_change['field_name']);
        self::assertEquals('float', (string) $this->xml->artifact->changeset[4]->field_change['type']);
        self::assertEquals($this->toExpectedDate(3234567400), (string) $this->xml->artifact->changeset[4]->submitted_on);

        self::assertEquals($this->toExpectedDate(1234543210), (string) $this->xml->artifact->changeset[5]->field_change->value);
        self::assertEquals('ISO8601', (string) $this->xml->artifact->changeset[5]->field_change->value['format']);
        self::assertEquals('field_18', (string) $this->xml->artifact->changeset[5]->field_change['field_name']);
        self::assertEquals('date', (string) $this->xml->artifact->changeset[5]->field_change['type']);
        self::assertEquals($this->toExpectedDate(3234567500), (string) $this->xml->artifact->changeset[5]->submitted_on);
    }

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsScalarNoHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_scalar_no_history');

        self::assertCount(1, $this->xml->artifact->changeset);

        $change = $this->xml->artifact->changeset[0]->field_change;
        self::assertCount(6, $change);

        $string = $this->findValue($change, 'field_14');
        self::assertEquals('The error code is 23232', (string) $string->value);
        $text = $this->findValue($change, 'field_15');
        self::assertEquals('some text', (string) $text->value);
        $int = $this->findValue($change, 'field_16');
        self::assertEquals('9001', (string) $int->value);
        $float = $this->findValue($change, 'field_17');
        self::assertEquals('66.98', (string) $float->value);
        $date = $this->findValue($change, 'field_18');
        self::assertEquals($this->toExpectedDate(1234543210), (string) $date->value);
    }

    public function testItCreatesALastChangesetAtImportTimeWhenHistoryDiffersFromCurrentStateWithScalarHalfHistory(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_scalar_half_history');

        self::assertCount(7, $this->xml->artifact->changeset);

        $change = $this->xml->artifact->changeset[6]->field_change;
        self::assertCount(5, $change);

        $string = $this->findValue($change, 'field_14');
        self::assertEquals('The error code is wrong', (string) $string->value);
        $text = $this->findValue($change, 'field_15');
        self::assertEquals('some rant', (string) $text->value);
        $int = $this->findValue($change, 'field_16');
        self::assertEquals('987', (string) $int->value);
        $float = $this->findValue($change, 'field_17');
        self::assertEquals('3.14', (string) $float->value);
        $date = $this->findValue($change, 'field_18');
        self::assertEquals($this->toExpectedDate(1234555555), (string) $date->value);
    }

    public function testItCreatesTheChangesetWithValueStoredOnArtifactTable(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_close_date_no_history');

        self::assertCount(2, $this->xml->artifact->changeset);

        self::assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('close_date', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('date', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->submitted_on);
    }

    public function testItCreatesTheChangesetWhenArtifactIsKeptReopen(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_close_date_kept_reopen');
        self::assertCount(3, $this->xml->artifact->changeset);

        // 1. Create artifact
        // 2. Close artifact
        self::assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        // 3. Reopen artifact
        self::assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals($this->toExpectedDate(1234900000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesTheChangesetWhenOneOpenAndCloseArtifact(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_close_date_history');

        self::assertCount(5, $this->xml->artifact->changeset);

        // 1. Create artifact
        // 2. Close artifact
        self::assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        // 3. Reopen artifact
        self::assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals($this->toExpectedDate(1234810000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        // 4. Close again artifact
        self::assertEquals($this->toExpectedDate(1234820000), (string) $this->xml->artifact->changeset[3]->field_change->value);
        self::assertEquals($this->toExpectedDate(1234820000), (string) $this->xml->artifact->changeset[3]->submitted_on);
        // 5. Change close date
        self::assertEquals($this->toExpectedDate(1234830000), (string) $this->xml->artifact->changeset[4]->field_change->value);
        self::assertEquals($this->toExpectedDate(1234840000), (string) $this->xml->artifact->changeset[4]->submitted_on);
    }

    public function testItCreatesTheInitialChangesetWithRecordedValue(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_status_no_history');

        self::assertCount(1, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'status_id');

        self::assertEquals('status_id', $field_change['field_name']);
        self::assertEquals('list', $field_change['type']);
        self::assertEquals('static', $field_change['bind']);
        self::assertEquals('Closed', $field_change->value);
    }

    public function testItAlwaysTrustValueInArtifactTableEvenIfThereIsAValueInValueList(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_status_history');

        self::assertCount(2, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[1]->field_change, 'status_id');

        self::assertEquals('status_id', $field_change['field_name']);
        self::assertEquals('list', $field_change['type']);
        self::assertEquals('static', $field_change['bind']);
        self::assertEquals('Closed', $field_change->value);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithStaticList(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_list_history');
        self::assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'category_id');
        self::assertEquals('UI', (string) $initial_change->value);
        self::assertEquals('category_id', (string) $initial_change['field_name']);
        self::assertEquals('list', (string) $initial_change['type']);
        self::assertEquals('static', (string) $initial_change['bind']);

        self::assertEquals('Database', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('category_id', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals('category_id', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesALastChangesetWhenHistoryWasNotRecorded(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_list_half_history');

        self::assertCount(3, $this->xml->artifact->changeset);

        self::assertEquals('UI', (string) $this->xml->artifact->changeset[2]->field_change->value);
        self::assertEquals('category_id', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate($_SERVER['REQUEST_TIME']), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItDoesntGetBlockedWhenThereIsNoDataStatusFieldValueList(): void
    {
        $this->logger->method('warning');
        $this->expectNotToPerformAssertions();
        $this->exportTrackerDataFromFixture('artifact_with_no_value_list_for_status_field');
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithUserList(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_list_history');

        self::assertCount(2, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'assigned_to');
        self::assertEquals('None', (string) $initial_change->value);
        self::assertEquals('assigned_to', (string) $initial_change['field_name']);
        self::assertEquals('list', (string) $initial_change['type']);
        self::assertEquals('users', (string) $initial_change['bind']);

        self::assertEquals('jeanjean', (string) $this->xml->artifact->changeset[1]->field_change->value);
        self::assertEquals('username', (string) $this->xml->artifact->changeset[1]->field_change->value['format']);
        self::assertEquals('assigned_to', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6880&group_id=101
     */
    public function testItDealsWithChangeOfDataTypeWhenSBisChangedIntoMSBThenChangedBackIntoSB(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_list_and_type_change');

        self::assertCount(1, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[0], 'assigned_to');
        self::assertEquals('jeanjean', (string) $field_change->value);
        self::assertEquals('list', (string) $field_change['type']);
        self::assertEquals('users', (string) $field_change['bind']);
    }

    public function testItCreatesAChangesetForEachHistoryEntryInHappyPath(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history');
        self::assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        self::assertEquals('', (string) $initial_change->value[0]);
        self::assertEquals('multiselect', (string) $initial_change['field_name']);
        self::assertEquals('list', (string) $initial_change['type']);
        self::assertEquals('static', (string) $initial_change['bind']);

        self::assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('Database', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('Stuff', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithMultipleStaticMultiSelectBoxesInHappyPath(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_multiple_multi_list_history');
        self::assertCount(5, $this->xml->artifact->changeset);

        $initial_change_msb   = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');
        $initial_change_msb_2 = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_2');

        self::assertEquals('', (string) $initial_change_msb->value[0]);
        self::assertEquals('multiselect', (string) $initial_change_msb['field_name']);
        self::assertEquals('list', (string) $initial_change_msb['type']);
        self::assertEquals('static', (string) $initial_change_msb['bind']);

        self::assertEquals('', (string) $initial_change_msb_2->value[0]);
        self::assertEquals('multiselect_2', (string) $initial_change_msb_2['field_name']);
        self::assertEquals('list', (string) $initial_change_msb_2['type']);
        self::assertEquals('static', (string) $initial_change_msb_2['bind']);

        self::assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('TV3', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('multiselect_2', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        self::assertEquals('Database', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        self::assertEquals('Stuff', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);

        self::assertEquals('TV5', (string) $this->xml->artifact->changeset[4]->field_change->value[0]);
        self::assertEquals('TV8_mont_blanc', (string) $this->xml->artifact->changeset[4]->field_change->value[1]);
        self::assertEquals('multiselect_2', (string) $this->xml->artifact->changeset[4]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[4]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[4]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234590000), (string) $this->xml->artifact->changeset[4]->submitted_on);
    }

    public function testItDoesNotCreateAChangesetForAnHistoryEntryIfItHasAZeroValue(): void
    {
        $this->logger->expects(self::once())->method('warning');

        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history_with_0');
        self::assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        self::assertEquals('', (string) $initial_change->value[0]);
        self::assertEquals('multiselect', (string) $initial_change['field_name']);
        self::assertEquals('list', (string) $initial_change['type']);
        self::assertEquals('static', (string) $initial_change['bind']);

        self::assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('Database', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('Stuff', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForAnHistoryEntryIfItHasAZeroValueInASetOfValues(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history_with_0_in_set_of_values');
        self::assertCount(4, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        self::assertEquals('', (string) $initial_change->value[0]);
        self::assertEquals('multiselect', (string) $initial_change['field_name']);
        self::assertEquals('list', (string) $initial_change['type']);
        self::assertEquals('static', (string) $initial_change['bind']);

        self::assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('Database', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        self::assertEquals('Database', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        self::assertEquals('Stuff', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);
    }

    public function testItDoesNotCreateAChangesetForAnHistoryEntryIfItHasALabelWithAComma(): void
    {
        $this->logger->expects(self::once())->method('warning');

        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history_with_a_comma_in_a_label');
        self::assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        self::assertEquals('', (string) $initial_change->value[0]);
        self::assertEquals('multiselect', (string) $initial_change['field_name']);
        self::assertEquals('list', (string) $initial_change['type']);
        self::assertEquals('static', (string) $initial_change['bind']);

        self::assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('PHP', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntryInHappyPathWithUserMultiList(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history');
        self::assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');

        self::assertEquals('', (string) $initial_change->value[0]);
        self::assertEquals('multiselect_user', (string) $initial_change['field_name']);
        self::assertEquals('list', (string) $initial_change['type']);
        self::assertEquals('users', (string) $initial_change['bind']);

        self::assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('nicolas', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('sandra', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithMultipleUserMultiSelectBoxesInHappyPath(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multiple_multi_list_history');
        self::assertCount(5, $this->xml->artifact->changeset);

        $initial_change_msb   = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');
        $initial_change_msb_2 = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user2');

        self::assertEquals('', (string) $initial_change_msb->value[0]);
        self::assertEquals('multiselect_user', (string) $initial_change_msb['field_name']);
        self::assertEquals('list', (string) $initial_change_msb['type']);
        self::assertEquals('users', (string) $initial_change_msb['bind']);

        self::assertEquals('', (string) $initial_change_msb_2->value[0]);
        self::assertEquals('multiselect_user2', (string) $initial_change_msb_2['field_name']);
        self::assertEquals('list', (string) $initial_change_msb_2['type']);
        self::assertEquals('users', (string) $initial_change_msb_2['bind']);

        self::assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('nicolas', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('multiselect_user2', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        self::assertEquals('nicolas', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        self::assertEquals('sandra', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);

        self::assertEquals('yannis', (string) $this->xml->artifact->changeset[4]->field_change->value[0]);
        self::assertEquals('sandra', (string) $this->xml->artifact->changeset[4]->field_change->value[1]);
        self::assertEquals('multiselect_user2', (string) $this->xml->artifact->changeset[4]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[4]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[4]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234590000), (string) $this->xml->artifact->changeset[4]->submitted_on);
    }

    public function testItDoesNotCreateAnExtraChangesetIfUsersAreNotInTheSameOrder(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history_with_user_in_wrong_order');
        self::assertCount(4, $this->xml->artifact->changeset);

        $initial_change_msb = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');

        self::assertEquals('', (string) $initial_change_msb->value[0]);
        self::assertEquals('multiselect_user', (string) $initial_change_msb['field_name']);
        self::assertEquals('list', (string) $initial_change_msb['type']);
        self::assertEquals('users', (string) $initial_change_msb['bind']);

        self::assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        self::assertEquals('sandra', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        self::assertEquals('nicolas', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);
    }

    public function testItDoesNotCreateAnExtraChangesetIfUsersLastValueIsNone(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history_finishing_by_none');
        self::assertCount(3, $this->xml->artifact->changeset);

        $initial_change_msb = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');

        self::assertEquals('', (string) $initial_change_msb->value[0]);
        self::assertEquals('multiselect_user', (string) $initial_change_msb['field_name']);
        self::assertEquals('list', (string) $initial_change_msb['type']);
        self::assertEquals('users', (string) $initial_change_msb['bind']);

        self::assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        self::assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        self::assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        self::assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        self::assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        self::assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6880&group_id=101
     */
    public function testItDealsWithChangeOfDataTypeWhenMSBisChangedIntoInSBandThenChangedBackInMSB(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history_with_data_change');

        self::assertCount(1, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[0], 'multiselect_user');
        self::assertEquals('nicolas', (string) $field_change->value[0]);
        self::assertEquals('sandra', (string) $field_change->value[1]);
        self::assertEquals('list', (string) $field_change['type']);
        self::assertEquals('users', (string) $field_change['bind']);
    }

    public function testItIgnoresMissingUser(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_and_missing_user');
        self::assertCount(1, $this->xml->artifact->changeset);
        $field_change = $this->findValue($this->xml->artifact->changeset[0], 'assigned_to');
        self::assertEquals('', (string) $field_change->value);
        self::assertEquals('list', (string) $field_change['type']);
        self::assertEquals('users', (string) $field_change['bind']);
    }
}
