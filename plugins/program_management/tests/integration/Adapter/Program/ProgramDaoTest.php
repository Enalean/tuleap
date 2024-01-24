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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Tuleap\DB\DBFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class ProgramDaoTest extends TestIntegrationTestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 60;
    private const PROGRAM_ID                   = 115;
    private static int $valid_artifact_id;
    private ProgramDaoProject $dao;

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $this->dao = new ProgramDaoProject();

        self::$valid_artifact_id = (int) $db->insertReturnId(
            'tracker_artifact',
            [
                'tracker_id' => self::PROGRAM_INCREMENT_TRACKER_ID,
                'last_changeset_id' => 3987,
                'submitted_by' => 143,
                'submitted_on' => 1234567890,
                'use_artifact_permissions' => 0,
                'per_tracker_artifact_id' => 1,
            ]
        );
        $db->insert('plugin_program_management_program', [
            'program_project_id' => self::PROGRAM_ID,
            'program_increment_tracker_id' => self::PROGRAM_INCREMENT_TRACKER_ID,
        ]);
    }

    public function testItRetrievesProgramOfProgramIncrement(): void
    {
        $program_increment = ProgramIncrementIdentifierBuilder::buildWithId(self::$valid_artifact_id);
        $program_id        = $this->dao->getProgramOfProgramIncrement($program_increment);
        self::assertSame(self::PROGRAM_ID, $program_id);
    }

    public function testItThrowsWhenProgramIncrementHasNoMatchingProgram(): void
    {
        $program_increment = ProgramIncrementIdentifierBuilder::buildWithId(404);
        $this->expectException(ProgramIncrementHasNoProgramException::class);
        $this->dao->getProgramOfProgramIncrement($program_increment);
    }
}
