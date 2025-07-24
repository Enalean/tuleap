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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\SearchFeatures;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CheckIsValidFeatureStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchPlannableFeaturesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleByProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeatureIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FEATURE_ID        = 87;
    private const FIRST_FEATURE_ID  = 623;
    private const SECOND_FEATURE_ID = 374;
    private VerifyFeatureIsVisibleByProgramStub $visible_by_program_verifier;
    private VerifyFeatureIsVisible $visible_verifier;
    private CheckIsValidFeatureStub $feature_checker;
    private SearchFeatures $feature_searcher;
    private \Closure $getId;
    private SearchPlannableFeatures $program_features_searcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->getId                       = static fn(FeatureIdentifier $feature): int => $feature->getId();
        $this->visible_by_program_verifier = VerifyFeatureIsVisibleByProgramStub::withAlwaysVisibleFeatures();
        $this->visible_verifier            = VerifyFeatureIsVisibleStub::withAlwaysVisibleFeatures();
        $this->feature_checker             = CheckIsValidFeatureStub::withAlwaysValidFeatures();
        $this->feature_searcher            = SearchFeaturesStub::withFeatureIds(
            self::FIRST_FEATURE_ID,
            self::SECOND_FEATURE_ID
        );

        $this->program_features_searcher = SearchPlannableFeaturesStub::withFeatureIds(
            self::FIRST_FEATURE_ID,
            self::SECOND_FEATURE_ID
        );
    }

    private function getFeatureFromProgram(?PermissionBypass $bypass): ?FeatureIdentifier
    {
        return FeatureIdentifier::fromIdAndProgram(
            $this->visible_by_program_verifier,
            self::FEATURE_ID,
            UserIdentifierStub::buildGenericUser(),
            ProgramIdentifierBuilder::build(),
            $bypass
        );
    }

    public function testItReturnsNullWhenProgramFeatureIsNotVisibleByUser(): void
    {
        $this->visible_by_program_verifier = VerifyFeatureIsVisibleByProgramStub::withFeatureNotVisibleOrNotInProgram();
        self::assertNull($this->getFeatureFromProgram(null));
    }

    public function testItBuildsAFeatureVisibleByProgram(): void
    {
        $feature = $this->getFeatureFromProgram(null);
        self::assertNotNull($feature);
        self::assertSame(self::FEATURE_ID, $feature->id);
        self::assertSame(self::FEATURE_ID, $feature->getId());
    }

    public function testItBuildsAValidProgramFeatureWithPermissionBypass(): void
    {
        $feature = $this->getFeatureFromProgram(new WorkflowUserPermissionBypass());
        self::assertNotNull($feature);
        self::assertSame(self::FEATURE_ID, $feature->id);
        self::assertSame(self::FEATURE_ID, $feature->getId());
    }

    private function getFeatureFromId(): FeatureIdentifier
    {
        return FeatureIdentifier::fromId(
            $this->feature_checker,
            self::FEATURE_ID,
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsAVisibleFeature(): void
    {
        $feature = $this->getFeatureFromId();
        self::assertSame(self::FEATURE_ID, $feature->id);
        self::assertSame(self::FEATURE_ID, $feature->getId());
    }

    private function getFeaturesFromProgramIncrement(): array
    {
        return FeatureIdentifier::buildCollectionFromProgramIncrement(
            $this->feature_searcher,
            $this->visible_verifier,
            ProgramIncrementIdentifierBuilder::buildWithId(866),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItReturnsFeaturesFromProgramIncrement(): void
    {
        $features    = $this->getFeaturesFromProgramIncrement();
        $feature_ids = array_map($this->getId, $features);
        self::assertContains(self::FIRST_FEATURE_ID, $feature_ids);
        self::assertContains(self::SECOND_FEATURE_ID, $feature_ids);
    }

    public function testItSkipsFeaturesUserCannotSee(): void
    {
        $this->visible_verifier = VerifyFeatureIsVisibleStub::withVisibleIds(self::SECOND_FEATURE_ID);
        $features               = $this->getFeaturesFromProgramIncrement();
        $feature_ids            = array_map($this->getId, $features);
        self::assertNotContains(self::FIRST_FEATURE_ID, $feature_ids);
        self::assertContains(self::SECOND_FEATURE_ID, $feature_ids);
    }

    public function testItReturnsEmptyArrayWhenProgramIncrementHasNoFeatures(): void
    {
        $this->feature_searcher = SearchFeaturesStub::withoutFeatures();
        self::assertCount(0, $this->getFeaturesFromProgramIncrement());
    }

    private function getFeaturesFromProgram(): array
    {
        return FeatureIdentifier::buildCollectionFromProgram(
            $this->program_features_searcher,
            $this->visible_verifier,
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItReturnsFeaturesOfProgram(): void
    {
        $features    = $this->getFeaturesFromProgram();
        $feature_ids = array_map($this->getId, $features);
        self::assertContains(self::FIRST_FEATURE_ID, $feature_ids);
        self::assertContains(self::SECOND_FEATURE_ID, $feature_ids);
    }

    public function testItSkipsFeaturesOfProgramUserCannotSee(): void
    {
        $this->visible_verifier = VerifyFeatureIsVisibleStub::withVisibleIds(self::SECOND_FEATURE_ID);
        $features               = $this->getFeaturesFromProgram();
        $feature_ids            = array_map($this->getId, $features);
        self::assertNotContains(self::FIRST_FEATURE_ID, $feature_ids);
        self::assertContains(self::SECOND_FEATURE_ID, $feature_ids);
    }

    public function testItReturnsEmptyArrayWhenProgramHasNoFeatures(): void
    {
        $this->program_features_searcher = SearchPlannableFeaturesStub::withoutFeatures();
        self::assertCount(0, $this->getFeaturesFromProgram());
    }
}
