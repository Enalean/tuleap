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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

final class SynchronizedFieldReferencesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TITLE_ID  = 615;
    private const STATUS_ID = 946;
    private GatherSynchronizedFieldsStub $gatherer;
    private ProgramIncrementTrackerIdentifier $program_increment_tracker;

    protected function setUp(): void
    {
        $this->gatherer = GatherSynchronizedFieldsStub::withFields(
            self::TITLE_ID,
            'tetraonid',
            self::STATUS_ID,
            'desolating'
        );

        $this->program_increment_tracker = ProgramIncrementTrackerIdentifier::fromId(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            11
        );
    }

    public function testItBuildsFromProgramIncrementTracker(): void
    {
        $fields = SynchronizedFieldReferences::fromProgramIncrementTracker(
            $this->gatherer,
            $this->program_increment_tracker
        );
        self::assertSame(self::TITLE_ID, $fields->title->getId());
        self::assertSame(self::STATUS_ID, $fields->status->getId());
    }
}
