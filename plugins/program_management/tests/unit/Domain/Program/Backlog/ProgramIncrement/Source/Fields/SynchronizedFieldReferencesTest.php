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

use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementTrackerIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SynchronizedFieldReferencesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TITLE_ID         = 615;
    private const DESCRIPTION_ID   = 843;
    private const STATUS_ID        = 946;
    private const START_DATE_ID    = 213;
    private const END_PERIOD_ID    = 126;
    private const ARTIFACT_LINK_ID = 128;
    private GatherSynchronizedFieldsStub $gatherer;
    private TrackerIdentifier $program_increment_tracker;

    protected function setUp(): void
    {
        $this->gatherer = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(
                self::TITLE_ID,
                self::DESCRIPTION_ID,
                self::STATUS_ID,
                self::START_DATE_ID,
                self::END_PERIOD_ID,
                self::ARTIFACT_LINK_ID
            )
        );

        $this->program_increment_tracker = ProgramIncrementTrackerIdentifierBuilder::buildWithId(11);
    }

    public function testItBuildsFromProgramIncrementTracker(): void
    {
        $fields = SynchronizedFieldReferences::fromTrackerIdentifier(
            $this->gatherer,
            $this->program_increment_tracker,
            null
        );
        self::assertSame(self::TITLE_ID, $fields->title->getId());
        self::assertSame(self::DESCRIPTION_ID, $fields->description->getId());
        self::assertSame(self::STATUS_ID, $fields->status->getId());
        self::assertSame(self::START_DATE_ID, $fields->start_date->getId());
        self::assertSame(self::END_PERIOD_ID, $fields->end_period->getId());
        self::assertSame(self::ARTIFACT_LINK_ID, $fields->artifact_link->getId());
    }
}
