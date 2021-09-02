<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Team;

use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeaturesStore;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramsOfTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;

final class PossibleParentHandlerTest extends TestCase
{
    private const FEATURE_ID = 123;
    private const PROGRAM_ID = 899;

    private FeaturesStore $feature_store;
    private PossibleParentSelectorEvent $possible_parent_selector;

    protected function setUp(): void
    {
        $this->feature_store = new class (self::FEATURE_ID) implements FeaturesStore
        {
            public function __construct(private int $feature_id)
            {
            }

            public function searchPlannableFeatures(ProgramIdentifier $program): array
            {
                return [];
            }

            public function searchOpenFeatures(ProgramIdentifier $program): array
            {
                return [
                    [ 'artifact_id' => $this->feature_id ]
                ];
            }
        };

        $this->possible_parent_selector = new class implements PossibleParentSelectorEvent {
            public int $project_id                   = 555;
            public ?array $features                  = null;
            public bool $can_create                  = true;
            public bool $tracker_is_in_root_planning = true;

            public function getUser(): UserIdentifier
            {
                return UserProxy::buildFromPFUser(UserTestBuilder::aUser()->build());
            }

            public function trackerIsInRootPlanning(): bool
            {
                return $this->tracker_is_in_root_planning;
            }

            public function getProjectId(): int
            {
                return $this->project_id;
            }

            public function disableCreate(): void
            {
                $this->can_create = false;
            }

            public function setPossibleParents(FeatureIdentifier ...$features): void
            {
                $this->features = $features;
            }
        };
    }

    public function testItHasOneParent(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals([self::FEATURE_ID], array_map(static fn (FeatureIdentifier $feature) => $feature->id, $this->possible_parent_selector->features));
    }

    public function testDisableCreateWhenInTheContextOfTeamAttachedToProgramToAvoidCrossProjectRedirections(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertFalse($this->possible_parent_selector->can_create);
    }

    public function testItDoesntFillPossibleParentWhenTrackerIsNotInATeam(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertNull($this->possible_parent_selector->features);
    }

    public function testAnArtifactThatCannotBeInTeamProjectBacklogWillNotHavePossibleParents(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::buildVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID),
            $this->feature_store,
        );

        $this->possible_parent_selector->tracker_is_in_root_planning = false;

        $possible_parent->handle($this->possible_parent_selector);

        assertNull($this->possible_parent_selector->features);
    }

    public function testItDoesntAddToPossibleParentsAnArtifactThatIsNotVisible(): void
    {
        $possible_parent = new PossibleParentHandler(
            VerifyIsVisibleFeatureStub::withNotVisibleFeature(),
            BuildProgramStub::stubValidProgram(),
            SearchProgramsOfTeamStub::buildPrograms(self::PROGRAM_ID),
            $this->feature_store,
        );

        $possible_parent->handle($this->possible_parent_selector);

        assertEquals([], $this->possible_parent_selector->features);
    }
}
