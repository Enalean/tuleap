<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\Logs;

use Tuleap\DB\DBFactory;

final class ModuleLogDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ModuleLogDao $dao;
    private int $tracker_id;
    private int $other_tracker_id;
    private int $artifact_id;
    private int $other_artifact_id;
    private int $changeset_id;
    private int $other_changeset_id;

    protected function setUp(): void
    {
        $this->dao                = new ModuleLogDao(\Tracker_ArtifactFactory::instance());
        $db                       = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->tracker_id         = (int) $db->insertReturnId('tracker', ['group_id' => 100]);
        $this->artifact_id        = (int) $db->insertReturnId('tracker_artifact', [
            'tracker_id'              => $this->tracker_id,
            'last_changeset_id'       => 1,
            'submitted_by'            => 101,
            'submitted_on'            => 123456790,
            'per_tracker_artifact_id' => 1,
        ]);
        $this->changeset_id       = (int) $db->insertReturnId('tracker_changeset', [
            'artifact_id'  => $this->artifact_id,
            'submitted_on' => 1234567890,
        ]);
        $this->other_tracker_id   = (int) $db->insertReturnId('tracker', ['group_id' => 100]);
        $this->other_artifact_id  = (int) $db->insertReturnId('tracker_artifact', [
            'tracker_id'              => $this->other_tracker_id,
            'last_changeset_id'       => 1,
            'submitted_by'            => 101,
            'submitted_on'            => 123456790,
            'per_tracker_artifact_id' => 1,
        ]);
        $this->other_changeset_id = (int) $db->insertReturnId('tracker_changeset', [
            'artifact_id'  => $this->other_artifact_id,
            'submitted_on' => 1234567890,
        ]);
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_tracker_cce_module_log');
        $db->run('DELETE FROM tracker WHERE id IN (?, ?)', $this->tracker_id, $this->other_tracker_id);
        $db->run('DELETE FROM tracker_artifact WHERE id IN (?, ?)', $this->artifact_id, $this->other_artifact_id);
        $db->run('DELETE FROM tracker_changeset WHERE id IN (?, ?)', $this->changeset_id, $this->other_changeset_id);
    }

    public function testItCanInsertANewPassedEntry(): void
    {
        $this->dao->saveModuleLogLine($this->createLogForChangeset($this->changeset_id, true));

        $result = $this->dao->searchLogsByTrackerId($this->tracker_id)[0]->log_line->toArray();

        self::assertEquals(ModuleLogLine::STATUS_PASSED, $result['status']);
        self::assertEquals($this->changeset_id, $result['changeset_id']);
        self::assertEquals('{"source": "payload"}', $result['source_payload_json']);
        self::assertEquals('{"generated": "payload"}', $result['generated_payload_json']);
        self::assertNull($result['error_message']);
        self::assertEquals(1234567890, $result['execution_date']);
    }

    public function testItCanInsertANewErrorEntry(): void
    {
        $this->dao->saveModuleLogLine($this->createLogForChangeset($this->changeset_id, false));

        $result = $this->dao->searchLogsByTrackerId($this->tracker_id)[0]->log_line->toArray();

        self::assertIsArray($result);
        self::assertEquals(ModuleLogLine::STATUS_ERROR, $result['status']);
        self::assertEquals($this->changeset_id, $result['changeset_id']);
        self::assertEquals('{"source": "payload"}', $result['source_payload_json']);
        self::assertNull($result['generated_payload_json']);
        self::assertEquals('Error message', $result['error_message']);
        self::assertEquals(1234567890, $result['execution_date']);
    }

    public function testItKeepsOnly50LogsMaxPerTracker(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->dao->saveModuleLogLine($this->createLogForChangeset($this->changeset_id, true));
        }
        $this->dao->saveModuleLogLine($this->createLogForChangeset($this->other_changeset_id, true));

        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        self::assertCount(50, $this->dao->searchLogsByTrackerId($this->tracker_id));
        self::assertCount(1, $this->dao->searchLogsByTrackerId($this->other_tracker_id));
    }

    public function testItDeleteLogs(): void
    {
        $this->dao->saveModuleLogLine($this->createLogForChangeset($this->changeset_id, true));
        $this->dao->saveModuleLogLine($this->createLogForChangeset($this->other_changeset_id, true));

        $this->dao->deleteLogsPerTracker($this->tracker_id);

        self::assertCount(0, $this->dao->searchLogsByTrackerId($this->tracker_id));
        self::assertCount(1, $this->dao->searchLogsByTrackerId($this->other_tracker_id));
    }

    private function createLogForChangeset(int $changeset_id, bool $passed): ModuleLogLine
    {
        if ($passed) {
            return ModuleLogLine::buildPassed(
                $changeset_id,
                '{"source": "payload"}',
                '{"generated": "payload"}',
                1234567890,
            );
        } else {
            return ModuleLogLine::buildError(
                $changeset_id,
                '{"source": "payload"}',
                'Error message',
                1234567890,
            );
        }
    }
}
