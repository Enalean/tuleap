<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\IncludeAssetsBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ArtifactTopBacklogActionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PrioritizeFeaturesPermissionVerifier
     */
    private $prioritize_features_permission_verifier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanStore
     */
    private $plan_store;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsExplicitTopBacklogDAO
     */
    private $artifacts_explicit_top_backlog_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlannedFeatureDAO
     */
    private $planned_feature_dao;

    protected function setUp(): void
    {
        $this->build_program                           = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier = \Mockery::mock(PrioritizeFeaturesPermissionVerifier::class);
        $this->plan_store                              = \Mockery::mock(PlanStore::class);
        $this->artifacts_explicit_top_backlog_dao      = \Mockery::mock(ArtifactsExplicitTopBacklogDAO::class);
        $this->planned_feature_dao                     = \Mockery::mock(PlannedFeatureDAO::class);
    }

    public function testBuildsActionForAnUnplannedArtifact(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $source_information  = new TopBacklogActionActifactSourceInformation(888, 140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(false);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(true);
        $this->planned_feature_dao->shouldReceive('isFeaturePlannedInAProgramIncrement')->andReturn(false);

        self::assertNotNull($this->getBuilder()->buildTopBacklogActionBuilder($source_information, $user));
    }

    public function testBuildsActionForAnArtifactInTheTopBacklog(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $source_information  = new TopBacklogActionActifactSourceInformation(999, 140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(true);

        self::assertNotNull($this->getBuilder()->buildTopBacklogActionBuilder($source_information, $user));
    }

    public function testNoActionIsBuiltForArtifactsThatAreNotInAProgramProject(): void
    {
        $source_information  = new TopBacklogActionActifactSourceInformation(400, 140, 102);
        $this->build_program = BuildProgramStub::stubInvalidProgram();
        self::assertNull($this->getBuilder()->buildTopBacklogActionBuilder($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoActionIsBuiltForUsersThatCannotPrioritizeFeatures(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $source_information  = new TopBacklogActionActifactSourceInformation(401, 140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(false);

        self::assertNull($this->getBuilder()->buildTopBacklogActionBuilder($source_information, $user));
    }

    public function testNoActionIsBuiltForArtifactsThatAreNotPlannable(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $source_information  = new TopBacklogActionActifactSourceInformation(2, 140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(false);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(false);

        self::assertNull($this->getBuilder()->buildTopBacklogActionBuilder($source_information, $user));
    }

    public function testNoActionIsBuiltForArtifactsThatArePlannedInAProgramIncrement(): void
    {
        $user                = UserTestBuilder::aUser()->build();
        $source_information  = new TopBacklogActionActifactSourceInformation(3, 140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(false);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(true);
        $this->planned_feature_dao->shouldReceive('isFeaturePlannedInAProgramIncrement')->andReturn(true);

        self::assertNull($this->getBuilder()->buildTopBacklogActionBuilder($source_information, $user));
    }

    private function getBuilder(): ArtifactTopBacklogActionBuilder
    {
        return new ArtifactTopBacklogActionBuilder(
            $this->build_program,
            $this->prioritize_features_permission_verifier,
            $this->plan_store,
            $this->artifacts_explicit_top_backlog_dao,
            $this->planned_feature_dao,
            new JavascriptAsset(IncludeAssetsBuilder::build(), 'action.js')
        );
    }
}
