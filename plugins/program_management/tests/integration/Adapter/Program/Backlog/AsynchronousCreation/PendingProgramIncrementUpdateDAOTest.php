<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\DB\DBFactory;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;

final class PendingProgramIncrementUpdateDAOTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID   = 38;
    private const USER_ID      = 148;
    private const CHANGESET_ID = 1594;
    private static int $active_artifact_id;
    private PendingProgramIncrementUpdateDAO $dao;

    public static function setUpBeforeClass(): void
    {
        $db                       = DBFactory::getMainTuleapDBConnection()->getDB();
        self::$active_artifact_id = (int) $db->insertReturnId(
            'tracker_artifact',
            [
                'tracker_id'               => self::TRACKER_ID,
                'last_changeset_id'        => self::CHANGESET_ID,
                'submitted_by'             => self::USER_ID,
                'submitted_on'             => 1234567890,
                'use_artifact_permissions' => 0,
                'per_tracker_artifact_id'  => 1
            ]
        );
    }

    protected function setUp(): void
    {
        $this->dao = new PendingProgramIncrementUpdateDAO();
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run('DELETE FROM plugin_program_management_pending_program_increment_update');
    }

    public static function tearDownAfterClass(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->delete('tracker_artifact', ['id' => self::$active_artifact_id]);
    }

    public function testAPendingUpdateCanBeStoredSearchedAndDeleted(): void
    {
        $update = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            self::$active_artifact_id,
            self::TRACKER_ID,
            (string) self::CHANGESET_ID
        );
        $this->dao->storeUpdate($update);

        $pending_update = $this->dao->searchUpdate(self::$active_artifact_id, self::USER_ID, self::CHANGESET_ID);
        self::assertSame(self::$active_artifact_id, $pending_update->getProgramIncrementId());
        self::assertSame(self::USER_ID, $pending_update->getUserId());
        self::assertSame(self::CHANGESET_ID, $pending_update->getChangesetId());

        $this->dao->deletePendingProgramIncrementUpdatesByProgramIncrementId(self::$active_artifact_id);
        self::assertNull($this->dao->searchUpdate(self::$active_artifact_id, self::USER_ID, self::CHANGESET_ID));
    }

    public function testItDeletesPendingUpdateWhenSearchingDeletedArtifact(): void
    {
        $program_increment_id = 404;
        $update               = ProgramIncrementUpdateBuilder::buildWithIds(
            self::USER_ID,
            $program_increment_id,
            self::TRACKER_ID,
            (string) self::CHANGESET_ID
        );
        $this->dao->storeUpdate($update);

        self::assertNull($this->dao->searchUpdate($program_increment_id, self::USER_ID, self::CHANGESET_ID));
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        self::assertNull(
            $db->row(
                'SELECT 1 FROM plugin_program_management_pending_program_increment_update WHERE program_increment_id = ?',
                $program_increment_id
            )
        );
    }
}
