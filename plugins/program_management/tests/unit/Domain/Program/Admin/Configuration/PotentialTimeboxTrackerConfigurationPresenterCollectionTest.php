<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\IterationTrackerIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchTrackersOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PotentialTimeboxTrackerConfigurationPresenterCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TRACKER_ID  = 300;
    private const SECOND_TRACKER_ID = 500;
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->program = ProgramForAdministrationIdentifierBuilder::build();
    }

    /**
     * @return ProgramSelectOptionConfiguration[]
     */
    private function getPresenters(TrackerReference|IterationTrackerIdentifier|null $program_tracker): array
    {
        return PotentialTimeboxTrackerConfigurationCollection::fromTimeboxTracker(
            PotentialTrackerCollection::fromProgram(
                SearchTrackersOfProgramStub::withTrackers(
                    TrackerReferenceStub::withId(self::FIRST_TRACKER_ID),
                    TrackerReferenceStub::withId(self::SECOND_TRACKER_ID),
                ),
                $this->program
            ),
            $program_tracker
        )->presenters;
    }

    public function testBuildTrackerPresentersWithCheckedTrackerIfExist(): void
    {
        $presenters = $this->getPresenters(TrackerReferenceStub::withId(self::FIRST_TRACKER_ID));

        self::assertCount(2, $presenters);
        self::assertSame(self::FIRST_TRACKER_ID, $presenters[0]->id);
        self::assertTrue($presenters[0]->is_selected);
        self::assertSame(self::SECOND_TRACKER_ID, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }

    public function testItBuildsWithCheckedTrackerFromIterationTracker(): void
    {
        $presenters = $this->getPresenters(IterationTrackerIdentifierBuilder::buildWithId(self::SECOND_TRACKER_ID));

        self::assertCount(2, $presenters);
        [$first_presenter, $second_presenter] = $presenters;
        self::assertSame(self::FIRST_TRACKER_ID, $first_presenter->id);
        self::assertFalse($first_presenter->is_selected);
        self::assertSame(self::SECOND_TRACKER_ID, $second_presenter->id);
        self::assertTrue($second_presenter->is_selected);
    }

    public function testBuildTrackerPresentersWithoutCheckedTracker(): void
    {
        $presenters = $this->getPresenters(null);

        self::assertCount(2, $presenters);
        self::assertSame(self::FIRST_TRACKER_ID, $presenters[0]->id);
        self::assertFalse($presenters[0]->is_selected);
        self::assertSame(self::SECOND_TRACKER_ID, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }
}
