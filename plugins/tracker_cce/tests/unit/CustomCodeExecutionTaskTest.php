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

namespace Tuleap\TrackerCCE;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

class CustomCodeExecutionTaskTest extends TestCase
{
    public function testItLogDebug(): void
    {
        $test_logger = new TestLogger();
        $task        = new CustomCodeExecutionTask($test_logger);
        $artifact    = ArtifactTestBuilder::anArtifact(45)->build();
        $changeset   = ChangesetTestBuilder::aChangeset('1')
            ->ofArtifact($artifact)
            ->build();

        $task->execute($changeset, false);
        self::assertTrue($test_logger->hasDebug("CustomCodeExecutionTask called on artifact #45 for changeset #1"));
    }
}
