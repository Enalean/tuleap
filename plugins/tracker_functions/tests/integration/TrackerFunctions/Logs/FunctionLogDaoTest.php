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

namespace Tuleap\TrackerFunctions\Logs;

use Tracker_ArtifactFactory;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FunctionLogDaoTest extends TestIntegrationTestCase
{
    private FunctionLogDao $dao;
    private int $tracker_id;
    private int $other_tracker_id;
    private int $changeset_id;
    private int $other_changeset_id;

    #[\Override]
    protected function setUp(): void
    {
        $db                       = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->dao                = new FunctionLogDao(Tracker_ArtifactFactory::instance());
        $this->tracker_id         = (int) $db->insertReturnId('tracker', ['group_id' => 100]);
        $artifact_id              = (int) $db->insertReturnId('tracker_artifact', [
            'tracker_id'              => $this->tracker_id,
            'last_changeset_id'       => 1,
            'submitted_by'            => 101,
            'submitted_on'            => 123456790,
            'per_tracker_artifact_id' => 1,
        ]);
        $this->changeset_id       = (int) $db->insertReturnId('tracker_changeset', [
            'artifact_id'  => $artifact_id,
            'submitted_on' => 1234567890,
        ]);
        $this->other_tracker_id   = (int) $db->insertReturnId('tracker', ['group_id' => 100]);
        $other_artifact_id        = (int) $db->insertReturnId('tracker_artifact', [
            'tracker_id'              => $this->other_tracker_id,
            'last_changeset_id'       => 1,
            'submitted_by'            => 101,
            'submitted_on'            => 123456790,
            'per_tracker_artifact_id' => 1,
        ]);
        $this->other_changeset_id = (int) $db->insertReturnId('tracker_changeset', [
            'artifact_id'  => $other_artifact_id,
            'submitted_on' => 1234567890,
        ]);
    }

    public function testItCanInsertANewPassedEntry(): void
    {
        $this->dao->saveFunctionLogLine($this->createLogForChangeset($this->changeset_id, true));

        $result = $this->dao->searchLogsByTrackerId($this->tracker_id)[0]->log_line;

        self::assertEquals(FunctionLogLineStatus::PASSED, $result->status);
        self::assertEquals($this->changeset_id, $result->changeset_id);
        self::assertNull($result->error_message);
        self::assertEquals(1234567890, $result->execution_date);

        $payloads = $this->dao->searchPayloadsByChangesetID($this->changeset_id)->unwrapOr(null);
        self::assertNotNull($payloads);
        self::assertEquals('{"source": "payload"}', $payloads->source_payload);
        self::assertEquals('{"generated": "payload"}', $payloads->generated_payload->unwrapOr(null));
        self::assertEquals($this->tracker_id, $payloads->tracker_id);
    }

    public function testItCanInsertANewErrorEntry(): void
    {
        $this->dao->saveFunctionLogLine($this->createLogForChangeset($this->changeset_id, false));

        $result = $this->dao->searchLogsByTrackerId($this->tracker_id)[0]->log_line;

        self::assertEquals(FunctionLogLineStatus::ERROR, $result->status);
        self::assertEquals($this->changeset_id, $result->changeset_id);
        self::assertEquals('Error message', $result->error_message);
        self::assertEquals(1234567890, $result->execution_date);

        $payloads = $this->dao->searchPayloadsByChangesetID($this->changeset_id)->unwrapOr(null);
        self::assertNotNull($payloads);
        self::assertEquals('{"source": "payload"}', $payloads->source_payload);
        self::assertTrue($payloads->generated_payload->isNothing());
        self::assertEquals($this->tracker_id, $payloads->tracker_id);
    }

    public function testItKeepsOnly50LogsMaxPerTracker(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $this->dao->saveFunctionLogLine($this->createLogForChangeset($this->changeset_id, true));
        }
        $this->dao->saveFunctionLogLine($this->createLogForChangeset($this->other_changeset_id, true));

        self::assertCount(50, $this->dao->searchLogsByTrackerId($this->tracker_id));
        self::assertCount(1, $this->dao->searchLogsByTrackerId($this->other_tracker_id));
    }

    public function testItDeleteLogs(): void
    {
        $this->dao->saveFunctionLogLine($this->createLogForChangeset($this->changeset_id, true));
        $this->dao->saveFunctionLogLine($this->createLogForChangeset($this->other_changeset_id, true));

        $this->dao->deleteLogsPerTracker($this->tracker_id);

        self::assertCount(0, $this->dao->searchLogsByTrackerId($this->tracker_id));
        self::assertCount(1, $this->dao->searchLogsByTrackerId($this->other_tracker_id));
    }

    private function createLogForChangeset(int $changeset_id, bool $passed): FunctionLogLineToSave
    {
        if ($passed) {
            return FunctionLogLineToSave::buildPassed(
                $changeset_id,
                '{"source": "payload"}',
                '{"generated": "payload"}',
                1234567890,
            );
        } else {
            return FunctionLogLineToSave::buildError(
                $changeset_id,
                '{"source": "payload"}',
                'Error message',
                1234567890,
            );
        }
    }
}
