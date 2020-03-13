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
final class ArtifactXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\ForgeConfigSandbox;

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

    protected function setUp() : void
    {
        parent::setUp();
        $this->dao                = \Mockery::spy(\ArtifactXMLExporterDao::class);
        $this->dom                = new DOMDocument("1.0", "UTF8");
        $this->archive            = \Mockery::spy(\ZipArchive::class);
        $node_helper              = new ArtifactXMLNodeHelper($this->dom);
        $attachment_exporter      = new ArtifactAttachmentXMLZipper($node_helper, $this->dao, $this->archive, false);
        $this->logger             = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->exporter           = new ArtifactXMLExporter($this->dao, $attachment_exporter, $node_helper, $this->logger);
        $this->fixtures_dir       = __DIR__ . '/_fixtures/';
        $this->expected_open_date = $this->toExpectedDate($this->open_date);
        ForgeConfig::store();
        ForgeConfig::set('sys_data_dir', dirname($this->fixtures_dir));
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
        $file_path = $this->fixtures_dir . $fixture . '.json';
        $fixture_content = file_get_contents($file_path);
        if ($fixture_content == false) {
            throw new Exception("Unable to load $file_path (mis-typed?)");
        }
        $json = json_decode($fixture_content, true, 512, JSON_THROW_ON_ERROR);

        foreach ($json['artifact'] as $tracker_id => $artifact_rows) {
            $this->dao->shouldReceive('searchArtifacts')->with($tracker_id)->andReturns(\TestHelper::argListToDar($artifact_rows));
        }
        foreach ($json['artifact_history'] as $artifact_id => $history_rows) {
            $this->dao->shouldReceive('searchHistory')->with($artifact_id)->andReturns(\TestHelper::argListToDar($history_rows));
        }
        if (isset($json['artifact_file']) && $json['artifact_file'] !== null) {
            foreach ($json['artifact_file'] as $artifact_id => $file_rows) {
                $this->dao->shouldReceive('searchFilesForArtifact')->with($artifact_id)->andReturns(\TestHelper::argListToDar($file_rows));
            }
        } else {
            $this->dao->shouldReceive('searchFilesForArtifact')->with($artifact_id)->andReturns(\TestHelper::emptyDar());
        }
        if (isset($json['artifact_file_search']) && $json['artifact_file_search'] !== null) {
            foreach ($json['artifact_file_search'] as $artifact_file) {
                $params = $artifact_file['parameters'];
                $this->dao->shouldReceive('searchFile')->with($params['artifact_id'], $params['filename'], $params['submitted_by'], $params['date'])->andReturns(\TestHelper::argListToDar($artifact_file['rows']));
            }
        } else {
            foreach ($json['artifact'] as $artifact_data) {
                $this->dao->shouldReceive('searchFile')->with($artifact_data[0]['artifact_id'])->andReturns(\TestHelper::emptyDar());
            }
        }
        if (isset($json['search_file_before']) && $json['search_file_before'] !== null) {
            foreach ($json['search_file_before'] as $artifact_file) {
                $params = $artifact_file['parameters'];
                $this->dao->shouldReceive('searchFileBefore')->with($params['artifact_id'], $params['filename'], $params['date'])->andReturns(\TestHelper::argListToDar($artifact_file['rows']));
            }
        } else {
            foreach ($json['artifact'] as $artifact_data) {
                $this->dao->shouldReceive('searchFileBefore')->with($artifact_data[0]['artifact_id'])->andReturns(\TestHelper::emptyDar());
            }
        }
        if (isset($json['search_cc_at']) && $json['search_cc_at'] !== null) {
            foreach ($json['search_cc_at'] as $artifact_cc) {
                $params = $artifact_cc['parameters'];
                $this->dao->shouldReceive('searchCCAt')->with($params['artifact_id'], $params['submitted_by'], $params['date'])->andReturns(\TestHelper::argListToDar($artifact_cc['rows']));
            }
        } else {
            foreach ($json['artifact'] as $artifact_data) {
                $this->dao->shouldReceive('searchCCAt')->with($artifact_data[0]['artifact_id'])->andReturns(\TestHelper::emptyDar());
            }
        }

        if (isset($json['permissions']) && $json['permissions'] !== null) {
            foreach ($json['permissions'] as $artifact_id => $perms) {
                $this->dao->shouldReceive('searchPermsForArtifact')->with($artifact_id)->andReturns(\TestHelper::argListToDar($perms));
            }
        } else {
            $this->dao->shouldReceive('searchPermsForArtifact')->andReturns(\TestHelper::emptyDar());
        }

        if (isset($json['artifact_field_value']) && $json['artifact_field_value'] !== null) {
            foreach ($json['artifact_field_value'] as $artifact_id => $history_rows) {
                $this->dao->shouldReceive('searchFieldValues')->with($artifact_id)->andReturns(\TestHelper::argListToDar($history_rows));
            }
        } else {
            $this->dao->shouldReceive('searchFieldValues')->andReturns(\TestHelper::emptyDar());
        }

        if (isset($json['artifact_field_value_list']) && $json['artifact_field_value_list'] !== null) {
            foreach ($json['artifact_field_value_list'] as $artifact_field_value_list) {
                $params = $artifact_field_value_list['parameters'];
                $this->dao->shouldReceive('searchFieldValuesList')->with($params['group_artifact_id'], $params['field_name'])->andReturns(\TestHelper::argListToDar($artifact_field_value_list['rows']));
            }
        } else {
            foreach ($json['artifact'] as $artifact_data) {
                $this->dao->shouldReceive('searchFieldValuesList')->with($artifact_data[0]['artifact_id'])->andReturns(\TestHelper::emptyDar());
            }
        }

        if (isset($json['user']) && $json['user'] !== null) {
            $all_users = array();
            foreach ($json['user'] as $user_id => $user_rows) {
                $this->dao->shouldReceive('searchUser')->with("$user_id")->andReturns(\TestHelper::argListToDar($user_rows));
                $all_users[] = array (
                    'user_id'   => $user_id,
                    'user_name' => $user_rows[0]['user_name']
                );
            }
            $this->dao->shouldReceive('getAllUsers')->andReturns(\TestHelper::argListToDar($all_users));
        } else {
            $this->dao->shouldReceive('searchUser')->andReturns(\TestHelper::emptyDar());
            $this->dao->shouldReceive('getAllUsers')->andReturns(\TestHelper::emptyDar());
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

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_without_any_history');

        $this->assertEquals('Le artifact without history', (string) $this->xml->artifact->changeset[0]->field_change[0]->value);
        $this->assertEquals('string', (string) $this->xml->artifact->changeset[0]->field_change[0]['type']);
        $this->assertEquals('summary', (string) $this->xml->artifact->changeset[0]->field_change[0]['field_name']);
        $this->assertEquals('Le original submission', (string) $this->xml->artifact->changeset[0]->field_change[1]->value);
        $this->assertEquals('text', (string) $this->xml->artifact->changeset[0]->field_change[1]['type']);
        $this->assertEquals('details', (string) $this->xml->artifact->changeset[0]->field_change[1]['field_name']);
        $this->assertEquals('1 - Ordinary', (string) $this->xml->artifact->changeset[0]->field_change[2]->value);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[0]->field_change[2]['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[0]->field_change[2]['bind']);
        $this->assertEquals('severity', (string) $this->xml->artifact->changeset[0]->field_change[2]['field_name']);
        $this->assertEquals($this->expected_open_date, (string) $this->xml->artifact->changeset[0]->submitted_on);
    }

    public function testItCreatesAnInitialChangesetBasedOnTheOldestValueKnownWhenThereIsHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        $this->assertEquals('Le artifact', (string) $this->xml->artifact->changeset[0]->field_change[0]->value);
        $this->assertEquals('Le original submission that will be updated', (string) $this->xml->artifact->changeset[0]->field_change[1]->value);
        $this->assertEquals('Le original submission', (string) $this->xml->artifact->changeset[2]->field_change[1]->value);
        $this->assertEquals($this->expected_open_date, (string) $this->xml->artifact->changeset[0]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntry() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        $this->assertEquals('Le artifact with history', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals($this->toExpectedDate(2234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);
        $this->assertEquals('Le artifact with full history', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesALastChangesetAtImportTimeWhenHistoryDiffersFromCurrentState() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_half_history');

        $this->assertEquals('Le artifact with half history', (string) $this->xml->artifact->changeset[3]->field_change->value);
        $this->assertEquals($this->toExpectedDate($_SERVER['REQUEST_TIME']), (string) $this->xml->artifact->changeset[3]->submitted_on);
    }

    public function testItDoesntMessPreviousArtifactWhenTryingToUpdateInitialChangeset() : void
    {
        $this->exportTrackerDataFromFixture('two_artifacts');

        $this->assertCount(2, $this->xml->artifact);

        $this->assertEquals('Le artifact with full history', (string) $this->xml->artifact[0]->changeset[0]->field_change->value);
        $this->assertEquals('Le artifact', (string) $this->xml->artifact[1]->changeset[0]->field_change->value);
        $this->assertEquals('The second one', (string) $this->xml->artifact[1]->changeset[1]->field_change->value);
    }

    public function testItHasChangesetPerComment(): void
    {
        $this->exportTrackerDataFromFixture('artifact_with_comment');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        $this->assertEquals('This is my comment', (string) $this->xml->artifact->changeset[1]->comments->comment->body);
        $this->assertEquals('text', (string) $this->xml->artifact->changeset[1]->comments->comment->body['format']);

        $this->assertEquals($this->toExpectedDate(1234569000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        $this->assertEquals('<p>With<strong> CHTEUMEULEU</strong></p>', (string) $this->xml->artifact->changeset[2]->comments->comment->body);
        $this->assertEquals('html', (string) $this->xml->artifact->changeset[2]->comments->comment->body['format']);
    }

    public function testItHasACommentVersions() : void
    {
        $this->logger->shouldReceive('warn')->never();
        $this->exportTrackerDataFromFixture('artifact_with_comment_updates');
        $this->assertCount(2, $this->xml->artifact->changeset);
        $this->assertCount(3, $this->xml->artifact->changeset[1]->comments->comment);

        $this->assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $comments = $this->xml->artifact->changeset[1]->comments;

        $this->assertEquals($this->toExpectedDate(1234568000), (string) $comments->comment[0]->submitted_on);

        $this->assertEquals('This is my comment', (string) $comments->comment[0]->body);
        $this->assertEquals('text', (string) $comments->comment[0]->body['format']);

        $this->assertEquals($this->toExpectedDate(1234569000), (string) $comments->comment[1]->submitted_on);
        $this->assertEquals('goofy', (string) $comments->comment[1]->submitted_by);
        $this->assertEquals('<p>With<strong> CHTEUMEULEU</strong></p>', (string) $comments->comment[1]->body);
        $this->assertEquals('html', (string) $comments->comment[1]->body['format']);

        $this->assertEquals($this->toExpectedDate(1234569500), (string) $comments->comment[2]->submitted_on);
        $this->assertEquals('goofy', (string) $comments->comment[2]->submitted_by);
        $this->assertEquals('<p>With<strong> HTML</strong></p>', (string) $comments->comment[2]->body);
        $this->assertEquals('html', (string) $comments->comment[2]->body['format']);
    }

    public function testItCreatesAChangesetWithOneAttachment() : void
    {
        $this->archive->shouldReceive('addEmptyDir')->with('data')->once();

        $this->exportTrackerDataFromFixture('artifact_with_one_attachment');
        $this->assertCount(2, $this->xml->artifact->changeset);

        $this->assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('file', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('File30', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);
        $this->assertEquals($this->toExpectedDate(3234567900), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertCount(1, $this->xml->artifact->file);
        $this->assertEquals('File30', (string) $this->xml->artifact->file[0]['id']);
        $this->assertEquals('A.png', (string) $this->xml->artifact->file[0]->filename);
        $this->assertEquals(12323, (int) $this->xml->artifact->file[0]->filesize);
        $this->assertEquals('image/png', (string) $this->xml->artifact->file[0]->filetype);
        $this->assertEquals('The screenshot', (string) $this->xml->artifact->file[0]->description);
    }

    public function testItCreatesAChangesetWithTwoAttachmentsWithSameName() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_two_attachments_same_name');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('File30', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);

        $this->assertEquals('attachment', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertCount(2, $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals('File31', (string) $this->xml->artifact->changeset[2]->field_change->value[0]['ref']);
        $this->assertEquals('File30', (string) $this->xml->artifact->changeset[2]->field_change->value[1]['ref']);
        $this->assertEquals($this->toExpectedDate(3234568000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        $this->assertCount(2, $this->xml->artifact->file);
        $this->assertEquals('File30', (string) $this->xml->artifact->file[0]['id']);
        $this->assertEquals('A.png', (string) $this->xml->artifact->file[0]->filename);
        $this->assertEquals(12323, (int) $this->xml->artifact->file[0]->filesize);
        $this->assertEquals('image/png', (string) $this->xml->artifact->file[0]->filetype);
        $this->assertEquals('The screenshot', (string) $this->xml->artifact->file[0]->description);

        $this->assertEquals('File31', (string) $this->xml->artifact->file[1]['id']);
        $this->assertEquals('A.png', (string) $this->xml->artifact->file[1]->filename);
        $this->assertEquals(50, (int) $this->xml->artifact->file[1]->filesize);
        $this->assertEquals('image/png', (string) $this->xml->artifact->file[1]->filetype);
        $this->assertEquals('The screenshot v2', (string) $this->xml->artifact->file[1]->description);
    }

    public function testItCreatesAChangesetWithDeletedAttachments() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_deleted_attachment');

        $this->assertCount(2, $this->xml->artifact->changeset);

        $this->assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertCount(1, $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('File31', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);
        $this->assertEquals($this->toExpectedDate(3234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertCount(1, $this->xml->artifact->file);
        $this->assertEquals('File31', (string) $this->xml->artifact->file[0]['id']);
        $this->assertEquals('zzz.pdf', (string) $this->xml->artifact->file[0]->filename);
    }

    public function testItCreatesAChangesetWithNullAttachments() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_null_attachment');

        $this->assertCount(1, $this->xml->artifact->changeset);
        foreach ($this->xml->artifact->changeset->field_change as $change) {
            $this->assertNotEquals('attachment', (string) $change['field_name']);
        }
    }

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoCCChanges() : void
    {
        $this->exportTrackerDataFromFixture('artifact_cc_no_changes');

        $this->assertCount(2, $this->xml->artifact->changeset[0]->field_change);
        $this->assertChangesItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory($this->xml->artifact->changeset[0]->field_change[0]);
        $this->assertChangesItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory($this->xml->artifact->changeset[0]->field_change[1]);
    }

    private function assertChangesItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistory(SimpleXMLElement $field_change): void
    {
        switch ($field_change['field_name']) {
            case 'cc':
                $this->assertEquals('open_list', (string) $field_change['type']);
                $this->assertEquals('users', (string) $field_change['bind']);
                $this->assertEquals('john@doe.org', (string) $field_change->value[0]);
                $this->assertEquals('jeanjean', (string) $field_change->value[1]);

                $this->assertFalse(isset($field_change->value[0]['format']));
                $this->assertFalse(isset($field_change->value[1]['format']));
                break;
            case 'summary':
                // Ok but we don't care
                break;
            default:
                throw new Exception('Unexpected field type: ' . $field_change['field_name']);
                break;
        }
    }

    public function testItCreatesTheTwoCCChangesChangeset() : void
    {
        $this->exportTrackerDataFromFixture('artifact_cc_add_new');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('jeanjean', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
    }

    public function testItCreatesChangesWithDeletedCC() : void
    {
        $this->exportTrackerDataFromFixture('artifact_cc_remove');

        $this->assertCount(2, $this->xml->artifact->changeset);

        $this->assertCount(3, $this->xml->artifact->changeset[0]->field_change->value);
        $this->assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[0]->field_change->value[0]);
        $this->assertEquals('jeanjean', (string) $this->xml->artifact->changeset[0]->field_change->value[1]);
        $this->assertEquals('bla@bla.org', (string) $this->xml->artifact->changeset[0]->field_change->value[2]);

        $this->assertCount(1, $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('john@doe.org', (string) $this->xml->artifact->changeset[1]->field_change->value);
    }

    public function testItSetNoneAsOriginalSeverityValue() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_severity_history');

        $this->assertEquals('1 - Ordinary', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals('severity', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
    }

    public function testItCreatesASingleChangesetWithSummaryAndAttachment() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_summary_and_attachment');
        $this->assertCount(1, $this->xml->artifact->changeset);

        $this->assertEquals($this->toExpectedDate(1234567890), (string) $this->xml->artifact->changeset[0]->submitted_on);

        // cannot guarranty the order of execution therefore specific assertion in dedicated method
        $this->assertCount(2, $this->xml->artifact->changeset[0]->field_change);
        $this->assertChangesItCreatesASingleChangesetWithSummaryAndAttachment($this->xml->artifact->changeset[0]->field_change[0]);
        $this->assertChangesItCreatesASingleChangesetWithSummaryAndAttachment($this->xml->artifact->changeset[0]->field_change[1]);

        $this->assertCount(1, $this->xml->artifact->file);
    }

    private function assertChangesItCreatesASingleChangesetWithSummaryAndAttachment(SimpleXMLElement $field_change)
    {
        switch ($field_change['field_name']) {
            case 'attachment':
                $this->assertEquals('File30', $field_change->value[0]['ref']);
                break;
            case 'summary':
                $this->assertEquals('Le artifact with full history', $field_change->value);
                break;
            default:
                throw new Exception('Unexpected field type: ' . $field_change['field_name']);
                break;
        }
    }

    public function testItCreatesChangesetWithSummaryAndAttachmentChange() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_summary_and_attachment_change');

        $this->assertCount(3, $this->xml->artifact->changeset);

        // Changeset1: original summary
        $this->assertEquals($this->toExpectedDate(1234567890), (string) $this->xml->artifact->changeset[0]->submitted_on);
        $this->assertEquals('summary', (string) $this->xml->artifact->changeset[0]->field_change['field_name']);
        $this->assertEquals('Le artifact with full history', (string) $this->xml->artifact->changeset[0]->field_change->value);

        // Changeset2: attachment
        $this->assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        $this->assertEquals('attachment', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('File30', (string) $this->xml->artifact->changeset[1]->field_change->value[0]['ref']);

        // Changeset3: new summary
        $this->assertEquals($this->toExpectedDate(1234569000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        $this->assertEquals('summary', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('Le artifact with updated summary', (string) $this->xml->artifact->changeset[2]->field_change->value);

        $this->assertCount(1, $this->xml->artifact->file);
    }

    public function testItCreatesChangesetWithAttachmentAndSummaryWhenHistoryDiffersFromCurrentState() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_summary_attachment_half_history');

        $this->assertCount(4, $this->xml->artifact->changeset);

        // Changeset1: original summary
        $this->assertEquals($this->toExpectedDate(1234567890), (string) $this->xml->artifact->changeset[0]->submitted_on);
        $this->assertEquals('summary', (string) $this->xml->artifact->changeset[0]->field_change['field_name']);
        $this->assertEquals('Le artifact with full history', (string) $this->xml->artifact->changeset[0]->field_change->value);

        // Changeset2: new summary
        $this->assertEquals($this->toExpectedDate(1234568000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        $this->assertEquals('summary', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('Le artifact with updated summary', (string) $this->xml->artifact->changeset[1]->field_change->value);

        // Changeset3: attachment
        $this->assertEquals($this->toExpectedDate(1234569000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        $this->assertEquals('attachment', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('File30', (string) $this->xml->artifact->changeset[2]->field_change->value[0]['ref']);

        // Changeset4: last summary update
        $this->assertEquals($this->toExpectedDate($_SERVER['REQUEST_TIME']), (string) $this->xml->artifact->changeset[3]->submitted_on);
        $this->assertEquals('summary', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        $this->assertEquals('Le artifact with half history', (string) $this->xml->artifact->changeset[3]->field_change->value);

        $this->assertCount(1, $this->xml->artifact->file);
    }

    public function testItDoesNotExportPermsIfThereIsNoPerms() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');
        foreach ($this->xml->artifact->xpath('changeset') as $changeset) {
            $this->assertThereIsNoPermissionsFieldChange($changeset);
        }
    }

    public function testItCreatesPermsOnArtifactAtTheVeryEnd() : void
    {
        $permissions_are_exported = false;
        $this->exportTrackerDataFromFixture('artifact_with_full_history_with_perms_on_artifact');

        $nb_of_changesets = count($this->xml->artifact->changeset);
        $last_changeset   = $this->xml->artifact->changeset[$nb_of_changesets - 1];

        foreach ($last_changeset->field_change as $field_change) {
            if ((string) $field_change['field_name'] !== 'permissions_on_artifact') {
                continue;
            }
            $this->assertEquals('permissions_on_artifact', (string) $field_change['type']);
            $this->assertEquals('1', (string) $field_change['use_perm']);
            $this->assertCount(2, $field_change->ugroup);
            $this->assertEquals('15', (string) $field_change->ugroup[0]['ugroup_id']);
            $this->assertEquals('101', (string) $field_change->ugroup[1]['ugroup_id']);
            $permissions_are_exported = true;
        }

        $this->assertTrue($permissions_are_exported);
    }

    public function testItTransformsNobodyIntoProjectAdministrators() : void
    {
        $permissions_are_exported = false;
        $this->exportTrackerDataFromFixture('artifact_with_full_history_with_perms_on_artifact_with_nobody');

        $nb_of_changesets = count($this->xml->artifact->changeset);
        $last_changeset   = $this->xml->artifact->changeset[$nb_of_changesets - 1];

        foreach ($last_changeset->field_change as $field_change) {
            if ((string) $field_change['field_name'] !== 'permissions_on_artifact') {
                continue;
            }
            $this->assertEquals('permissions_on_artifact', (string) $field_change['type']);
            $this->assertEquals('1', (string) $field_change['use_perm']);
            $this->assertCount(1, $field_change->ugroup);
            $this->assertEquals('4', (string) $field_change->ugroup[0]['ugroup_id']);
            $permissions_are_exported = true;
        }

        $this->assertTrue($permissions_are_exported);
    }

    public function testItDoesNotExportPermissionsInFirstChangesets() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_full_history');

        $first_changesets = array_slice($this->xml->artifact->xpath('changeset'), 0, -1);
        foreach ($first_changesets as $changeset) {
            $this->assertThereIsNoPermissionsFieldChange($changeset);
        }
    }

    private function assertThereIsNoPermissionsFieldChange(SimpleXMLElement $changeset): void
    {
        foreach ($changeset->field_change as $field_change) {
            $this->assertNotEquals('permissions_on_artifact', (string) $field_change['field_name']);
        }
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithStringHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_string_history');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('The error code is 23232', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('field_14', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('string', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('The error code is not returned', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals('field_14', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('string', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsNoHistoryWithString() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_string_no_history');

        $this->assertCount(1, $this->xml->artifact->changeset);

        $this->assertCount(2, $this->xml->artifact->changeset[0]->field_change);
        $this->assertChangesItCreatesASingleChangesetWithSummaryAndString($this->xml->artifact->changeset[0]->field_change[0]);
        $this->assertChangesItCreatesASingleChangesetWithSummaryAndString($this->xml->artifact->changeset[0]->field_change[1]);
    }

    private function assertChangesItCreatesASingleChangesetWithSummaryAndString(SimpleXMLElement $field_change): void
    {
        switch ($field_change['field_name']) {
            case 'field_14':
                $this->assertEquals('The error code is not returned', $field_change->value);
                break;
            case 'summary':
                $this->assertEquals('Le artifact with full history', $field_change->value);
                break;
            default:
                throw new Exception('Unexpected field type: ' . $field_change['field_name']);
                break;
        }
    }

    public function testItDoesntCreateAnExtraChangesetWhenThereIsAFloatToStringConversionWithTrailingZero() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_float_history');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('66.98', (string) $this->xml->artifact->changeset[1]->field_change->value);

        $this->assertEquals('2048', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItReturnsZeroIfNoNewValue() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_float_history_with_no_value');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('66.98', (string) $this->xml->artifact->changeset[1]->field_change->value);

        $this->assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItConvertsHistoricalValuesWhenFieldTypeChanged() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_float_history_with_string_value');
        $this->assertCount(4, $this->xml->artifact->changeset);

        $this->assertEquals('0', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('2048', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals('43.0', (string) $this->xml->artifact->changeset[3]->field_change->value);
    }

    public function testItDoesntCreateAnExtraChangesetWhenThereIsAnIntToStringConversionWithTrailingZero() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_integer_history');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('66', (string) $this->xml->artifact->changeset[1]->field_change->value);

        $this->assertEquals('2048', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItReturnsZeroIfNoNewValueWithIntegerHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_integer_history_with_no_value');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('66', (string) $this->xml->artifact->changeset[1]->field_change->value);

        $this->assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value);
    }

    public function testItConvertsHistoricalValuesWhenFieldTypeChangedWithIntegerAndStringHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_integer_history_with_string_value');
        $this->assertCount(5, $this->xml->artifact->changeset);

        $this->assertEquals('0', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals('4', (string) $this->xml->artifact->changeset[3]->field_change->value);
        $this->assertEquals('43', (string) $this->xml->artifact->changeset[4]->field_change->value);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithScalarHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_scalar_history');

        $this->assertCount(6, $this->xml->artifact->changeset);

        $this->assertCount(6, $this->xml->artifact->changeset[0]->field_change);
        $this->assertEquals('', (string) $this->findValue($this->xml->artifact->changeset[0]->field_change, 'field_18')->value);

        $this->assertEquals('The error code is 23232', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('field_14', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('string', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(3234567100), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals("some text", (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals('field_15', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('text', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(3234567200), (string) $this->xml->artifact->changeset[2]->submitted_on);

        $this->assertEquals("9001", (string) $this->xml->artifact->changeset[3]->field_change->value);
        $this->assertEquals('field_16', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        $this->assertEquals('int', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(3234567300), (string) $this->xml->artifact->changeset[3]->submitted_on);

        $this->assertEquals("66.98", (string) $this->xml->artifact->changeset[4]->field_change->value);
        $this->assertEquals('field_17', (string) $this->xml->artifact->changeset[4]->field_change['field_name']);
        $this->assertEquals('float', (string) $this->xml->artifact->changeset[4]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(3234567400), (string) $this->xml->artifact->changeset[4]->submitted_on);

        $this->assertEquals($this->toExpectedDate(1234543210), (string) $this->xml->artifact->changeset[5]->field_change->value);
        $this->assertEquals('ISO8601', (string) $this->xml->artifact->changeset[5]->field_change->value['format']);
        $this->assertEquals('field_18', (string) $this->xml->artifact->changeset[5]->field_change['field_name']);
        $this->assertEquals('date', (string) $this->xml->artifact->changeset[5]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(3234567500), (string) $this->xml->artifact->changeset[5]->submitted_on);
    }

    public function testItCreatesAnInitialChangesetATheTimeOfOpenDateWhenThereIsScalarNoHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_scalar_no_history');

        $this->assertCount(1, $this->xml->artifact->changeset);

        $change = $this->xml->artifact->changeset[0]->field_change;
        $this->assertCount(6, $change);

        $string = $this->findValue($change, 'field_14');
        $this->assertEquals('The error code is 23232', (string) $string->value);
        $text   = $this->findValue($change, 'field_15');
        $this->assertEquals('some text', (string) $text->value);
        $int    = $this->findValue($change, 'field_16');
        $this->assertEquals('9001', (string) $int->value);
        $float  = $this->findValue($change, 'field_17');
        $this->assertEquals('66.98', (string) $float->value);
        $date   = $this->findValue($change, 'field_18');
        $this->assertEquals($this->toExpectedDate(1234543210), (string) $date->value);
    }

    public function testItCreatesALastChangesetAtImportTimeWhenHistoryDiffersFromCurrentStateWithScalarHalfHistory() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_scalar_half_history');

        $this->assertCount(7, $this->xml->artifact->changeset);

        $change = $this->xml->artifact->changeset[6]->field_change;
        $this->assertCount(5, $change);

        $string = $this->findValue($change, 'field_14');
        $this->assertEquals('The error code is wrong', (string) $string->value);
        $text   = $this->findValue($change, 'field_15');
        $this->assertEquals('some rant', (string) $text->value);
        $int    = $this->findValue($change, 'field_16');
        $this->assertEquals('987', (string) $int->value);
        $float  = $this->findValue($change, 'field_17');
        $this->assertEquals('3.14', (string) $float->value);
        $date   = $this->findValue($change, 'field_18');
        $this->assertEquals($this->toExpectedDate(1234555555), (string) $date->value);
    }

    public function testItCreatesTheChangesetWithValueStoredOnArtifactTable() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_close_date_no_history');

        $this->assertCount(2, $this->xml->artifact->changeset);

        $this->assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('close_date', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('date', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->submitted_on);
    }

    public function testItCreatesTheChangesetWhenArtifactIsKeptReopen() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_close_date_kept_reopen');
        $this->assertCount(3, $this->xml->artifact->changeset);

        // 1. Create artifact
        // 2. Close artifact
        $this->assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        // 3. Reopen artifact
        $this->assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals($this->toExpectedDate(1234900000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesTheChangesetWhenOneOpenAndCloseArtifact() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_close_date_history');

        $this->assertCount(5, $this->xml->artifact->changeset);

        // 1. Create artifact
        // 2. Close artifact
        $this->assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals($this->toExpectedDate(1234800000), (string) $this->xml->artifact->changeset[1]->submitted_on);
        // 3. Reopen artifact
        $this->assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals($this->toExpectedDate(1234810000), (string) $this->xml->artifact->changeset[2]->submitted_on);
        // 4. Close again artifact
        $this->assertEquals($this->toExpectedDate(1234820000), (string) $this->xml->artifact->changeset[3]->field_change->value);
        $this->assertEquals($this->toExpectedDate(1234820000), (string) $this->xml->artifact->changeset[3]->submitted_on);
        // 5. Change close date
        $this->assertEquals($this->toExpectedDate(1234830000), (string) $this->xml->artifact->changeset[4]->field_change->value);
        $this->assertEquals($this->toExpectedDate(1234840000), (string) $this->xml->artifact->changeset[4]->submitted_on);
    }

    public function testItCreatesTheInitialChangesetWithRecordedValue() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_status_no_history');

        $this->assertCount(1, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'status_id');

        $this->assertEquals('status_id', $field_change['field_name']);
        $this->assertEquals('list', $field_change['type']);
        $this->assertEquals('static', $field_change['bind']);
        $this->assertEquals('Closed', $field_change->value);
    }

    public function testItAlwaysTrustValueInArtifactTableEvenIfThereIsAValueInValueList() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_status_history');

        $this->assertCount(2, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[1]->field_change, 'status_id');

        $this->assertEquals('status_id', $field_change['field_name']);
        $this->assertEquals('list', $field_change['type']);
        $this->assertEquals('static', $field_change['bind']);
        $this->assertEquals('Closed', $field_change->value);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithStaticList() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_list_history');
        $this->assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'category_id');
        $this->assertEquals('UI', (string) $initial_change->value);
        $this->assertEquals('category_id', (string) $initial_change['field_name']);
        $this->assertEquals('list', (string) $initial_change['type']);
        $this->assertEquals('static', (string) $initial_change['bind']);

        $this->assertEquals('Database', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('category_id', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals('category_id', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesALastChangesetWhenHistoryWasNotRecorded() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_list_half_history');

        $this->assertCount(3, $this->xml->artifact->changeset);

        $this->assertEquals('UI', (string) $this->xml->artifact->changeset[2]->field_change->value);
        $this->assertEquals('category_id', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate($_SERVER['REQUEST_TIME']), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItDoesntGetBlockedWhenThereIsNoDataStatusFieldValueList() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_no_value_list_for_status_field');
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithUserList() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_list_history');

        $this->assertCount(2, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'assigned_to');
        $this->assertEquals('', (string) $initial_change->value);
        $this->assertEquals('assigned_to', (string) $initial_change['field_name']);
        $this->assertEquals('list', (string) $initial_change['type']);
        $this->assertEquals('users', (string) $initial_change['bind']);

        $this->assertEquals('jeanjean', (string) $this->xml->artifact->changeset[1]->field_change->value);
        $this->assertEquals('username', (string) $this->xml->artifact->changeset[1]->field_change->value['format']);
        $this->assertEquals('assigned_to', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6880&group_id=101
     */
    public function testItDealsWithChangeOfDataTypeWhenSBisChangedIntoMSBThenChangedBackIntoSB() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_list_and_type_change');

        $this->assertCount(1, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[0], 'assigned_to');
        $this->assertEquals('jeanjean', (string) $field_change->value);
        $this->assertEquals('list', (string) $field_change['type']);
        $this->assertEquals('users', (string) $field_change['bind']);
    }

    public function testItCreatesAChangesetForEachHistoryEntryInHappyPath() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history');
        $this->assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        $this->assertEquals('', (string) $initial_change->value[0]);
        $this->assertEquals('multiselect', (string) $initial_change['field_name']);
        $this->assertEquals('list', (string) $initial_change['type']);
        $this->assertEquals('static', (string) $initial_change['bind']);

        $this->assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('Database', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('Stuff', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithMultipleStaticMultiSelectBoxesInHappyPath() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_multiple_multi_list_history');
        $this->assertCount(5, $this->xml->artifact->changeset);

        $initial_change_msb   = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');
        $initial_change_msb_2 = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_2');

        $this->assertEquals('', (string) $initial_change_msb->value[0]);
        $this->assertEquals('multiselect', (string) $initial_change_msb['field_name']);
        $this->assertEquals('list', (string) $initial_change_msb['type']);
        $this->assertEquals('static', (string) $initial_change_msb['bind']);

        $this->assertEquals('', (string) $initial_change_msb_2->value[0]);
        $this->assertEquals('multiselect_2', (string) $initial_change_msb_2['field_name']);
        $this->assertEquals('list', (string) $initial_change_msb_2['type']);
        $this->assertEquals('static', (string) $initial_change_msb_2['bind']);

        $this->assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('TV3', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('multiselect_2', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        $this->assertEquals('Database', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        $this->assertEquals('Stuff', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);

        $this->assertEquals('TV5', (string) $this->xml->artifact->changeset[4]->field_change->value[0]);
        $this->assertEquals('TV8_mont_blanc', (string) $this->xml->artifact->changeset[4]->field_change->value[1]);
        $this->assertEquals('multiselect_2', (string) $this->xml->artifact->changeset[4]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[4]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[4]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234590000), (string) $this->xml->artifact->changeset[4]->submitted_on);
    }

    public function testItDoesNotCreateAChangesetForAnHistoryEntryIfItHasAZeroValue() : void
    {
        $this->logger->shouldReceive('warning')->once();

        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history_with_0');
        $this->assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        $this->assertEquals('', (string) $initial_change->value[0]);
        $this->assertEquals('multiselect', (string) $initial_change['field_name']);
        $this->assertEquals('list', (string) $initial_change['type']);
        $this->assertEquals('static', (string) $initial_change['bind']);

        $this->assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('Database', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('Stuff', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForAnHistoryEntryIfItHasAZeroValueInASetOfValues() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history_with_0_in_set_of_values');
        $this->assertCount(4, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        $this->assertEquals('', (string) $initial_change->value[0]);
        $this->assertEquals('multiselect', (string) $initial_change['field_name']);
        $this->assertEquals('list', (string) $initial_change['type']);
        $this->assertEquals('static', (string) $initial_change['bind']);

        $this->assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('Database', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('0', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        $this->assertEquals('Database', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        $this->assertEquals('Stuff', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);
    }

    public function testItDoesNotCreateAChangesetForAnHistoryEntryIfItHasALabelWithAComma() : void
    {
        $this->logger->shouldReceive('warning')->once();

        $this->exportTrackerDataFromFixture('artifact_with_static_multi_list_history_with_a_comma_in_a_label');
        $this->assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect');

        $this->assertEquals('', (string) $initial_change->value[0]);
        $this->assertEquals('multiselect', (string) $initial_change['field_name']);
        $this->assertEquals('list', (string) $initial_change['type']);
        $this->assertEquals('static', (string) $initial_change['bind']);

        $this->assertEquals('UI', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('PHP', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('multiselect', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('static', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntryInHappyPathWithUserMultiList() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history');
        $this->assertCount(3, $this->xml->artifact->changeset);

        $initial_change = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');

        $this->assertEquals('', (string) $initial_change->value[0]);
        $this->assertEquals('multiselect_user', (string) $initial_change['field_name']);
        $this->assertEquals('list', (string) $initial_change['type']);
        $this->assertEquals('users', (string) $initial_change['bind']);

        $this->assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('nicolas', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('sandra', (string) $this->xml->artifact->changeset[2]->field_change->value[1]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    public function testItCreatesAChangesetForEachHistoryEntryWithMultipleUserMultiSelectBoxesInHappyPath() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multiple_multi_list_history');
        $this->assertCount(5, $this->xml->artifact->changeset);

        $initial_change_msb   = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');
        $initial_change_msb_2 = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user2');

        $this->assertEquals('', (string) $initial_change_msb->value[0]);
        $this->assertEquals('multiselect_user', (string) $initial_change_msb['field_name']);
        $this->assertEquals('list', (string) $initial_change_msb['type']);
        $this->assertEquals('users', (string) $initial_change_msb['bind']);

        $this->assertEquals('', (string) $initial_change_msb_2->value[0]);
        $this->assertEquals('multiselect_user2', (string) $initial_change_msb_2['field_name']);
        $this->assertEquals('list', (string) $initial_change_msb_2['type']);
        $this->assertEquals('users', (string) $initial_change_msb_2['bind']);

        $this->assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('nicolas', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('multiselect_user2', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        $this->assertEquals('nicolas', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        $this->assertEquals('sandra', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);

        $this->assertEquals('yannis', (string) $this->xml->artifact->changeset[4]->field_change->value[0]);
        $this->assertEquals('sandra', (string) $this->xml->artifact->changeset[4]->field_change->value[1]);
        $this->assertEquals('multiselect_user2', (string) $this->xml->artifact->changeset[4]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[4]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[4]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234590000), (string) $this->xml->artifact->changeset[4]->submitted_on);
    }

    public function testItDoesNotCreateAnExtraChangesetIfUsersAreNotInTheSameOrder() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history_with_user_in_wrong_order');
        $this->assertCount(4, $this->xml->artifact->changeset);

        $initial_change_msb = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');

        $this->assertEquals('', (string) $initial_change_msb->value[0]);
        $this->assertEquals('multiselect_user', (string) $initial_change_msb['field_name']);
        $this->assertEquals('list', (string) $initial_change_msb['type']);
        $this->assertEquals('users', (string) $initial_change_msb['bind']);

        $this->assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);

        $this->assertEquals('sandra', (string) $this->xml->artifact->changeset[3]->field_change->value[0]);
        $this->assertEquals('nicolas', (string) $this->xml->artifact->changeset[3]->field_change->value[1]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[3]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[3]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[3]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234580000), (string) $this->xml->artifact->changeset[3]->submitted_on);
    }

    public function testItDoesNotCreateAnExtraChangesetIfUsersLastValueIsNone() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history_finishing_by_none');
        $this->assertCount(3, $this->xml->artifact->changeset);

        $initial_change_msb = $this->findValue($this->xml->artifact->changeset[0]->field_change, 'multiselect_user');

        $this->assertEquals('', (string) $initial_change_msb->value[0]);
        $this->assertEquals('multiselect_user', (string) $initial_change_msb['field_name']);
        $this->assertEquals('list', (string) $initial_change_msb['type']);
        $this->assertEquals('users', (string) $initial_change_msb['bind']);

        $this->assertEquals('yannis', (string) $this->xml->artifact->changeset[1]->field_change->value[0]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[1]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[1]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[1]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234567890), (string) $this->xml->artifact->changeset[1]->submitted_on);

        $this->assertEquals('', (string) $this->xml->artifact->changeset[2]->field_change->value[0]);
        $this->assertEquals('multiselect_user', (string) $this->xml->artifact->changeset[2]->field_change['field_name']);
        $this->assertEquals('list', (string) $this->xml->artifact->changeset[2]->field_change['type']);
        $this->assertEquals('users', (string) $this->xml->artifact->changeset[2]->field_change['bind']);
        $this->assertEquals($this->toExpectedDate(3234570000), (string) $this->xml->artifact->changeset[2]->submitted_on);
    }

    /**
     * @see https://tuleap.net/plugins/tracker/?aid=6880&group_id=101
     */
    public function testItDealsWithChangeOfDataTypeWhenMSBisChangedIntoInSBandThenChangedBackInMSB() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_history_with_data_change');

        $this->assertCount(1, $this->xml->artifact->changeset);

        $field_change = $this->findValue($this->xml->artifact->changeset[0], 'multiselect_user');
        $this->assertEquals('nicolas', (string) $field_change->value[0]);
        $this->assertEquals('sandra', (string) $field_change->value[1]);
        $this->assertEquals('list', (string) $field_change['type']);
        $this->assertEquals('users', (string) $field_change['bind']);
    }

    public function testItIgnoresMissingUser() : void
    {
        $this->exportTrackerDataFromFixture('artifact_with_user_multi_list_and_missing_user');
        $this->assertCount(1, $this->xml->artifact->changeset);
        $field_change = $this->findValue($this->xml->artifact->changeset[0], 'assigned_to');
        $this->assertEquals('', (string) $field_change->value);
        $this->assertEquals('list', (string) $field_change['type']);
        $this->assertEquals('users', (string) $field_change['bind']);
    }
}
