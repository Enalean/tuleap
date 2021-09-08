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

namespace Tuleap\ProgramManagement\Domain\Program;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProgramIdentifierTest extends TestCase
{
    public function testItBuildsAProgramIdentifierFromAProjectId(): void
    {
        $project_id = 101;
        $program    = ProgramIdentifierBuilder::buildWithId($project_id);
        self::assertSame($project_id, $program->getId());
    }

    public function testItBuildsWithBypass(): void
    {
        $project_id = 101;
        $program    = ProgramIdentifierBuilder::buildWithIdAndPass($project_id);
        self::assertSame($project_id, $program->getId());
    }

    public function testItBuildsAProgramIdentifierFromReplicationData(): void
    {
        $replication_data = ReplicationDataBuilder::buildWithProjectId(102);
        $program          = ProgramIdentifier::fromReplicationData($replication_data);
        self::assertSame(102, $program->getId());
    }
}
