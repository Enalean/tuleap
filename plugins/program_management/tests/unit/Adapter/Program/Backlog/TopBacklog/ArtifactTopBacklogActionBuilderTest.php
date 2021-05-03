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
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\Program;
use Tuleap\Test\Builders\IncludeAssetsBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ArtifactTopBacklogActionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildProgram
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
    /**
     * @var ArtifactTopBacklogActionBuilder
     */
    private $action_builder;

    protected function setUp(): void
    {
        $this->build_program                           = \Mockery::mock(BuildProgram::class);
        $this->prioritize_features_permission_verifier = \Mockery::mock(PrioritizeFeaturesPermissionVerifier::class);
        $this->plan_store                              = \Mockery::mock(PlanStore::class);
        $this->artifacts_explicit_top_backlog_dao      = \Mockery::mock(ArtifactsExplicitTopBacklogDAO::class);
        $this->planned_feature_dao                     = \Mockery::mock(PlannedFeatureDAO::class);

        $this->action_builder = new ArtifactTopBacklogActionBuilder(
            $this->build_program,
            $this->prioritize_features_permission_verifier,
            $this->plan_store,
            $this->artifacts_explicit_top_backlog_dao,
            $this->planned_feature_dao,
            new JavascriptAsset(IncludeAssetsBuilder::build(), 'action.js')
        );
    }

    public function testBuildsActionForAnUnplannedArtifact(): void
    {
        $source_information = new TopBacklogActionActifactSourceInformation(888, 140, 102);
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(false);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(true);
        $this->planned_feature_dao->shouldReceive('isFeaturePlannedInAProgramIncrement')->andReturn(false);

        self::assertNotNull($this->action_builder->buildTopBacklogActionBuilder($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testBuildsActionForAnArtifactInTheTopBacklog(): void
    {
        $source_information = new TopBacklogActionActifactSourceInformation(999, 140, 102);
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(true);

        self::assertNotNull($this->action_builder->buildTopBacklogActionBuilder($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoActionIsBuiltForArtifactsThatAreNotInAProgramProject(): void
    {
        $source_information = new TopBacklogActionActifactSourceInformation(400, 140, 102);
        $this->build_program->shouldReceive('buildExistingProgramProject')->andThrow(new ProjectIsNotAProgramException(102));

        self::assertNull($this->action_builder->buildTopBacklogActionBuilder($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoActionIsBuiltForUsersThatCannotPrioritizeFeatures(): void
    {
        $source_information = new TopBacklogActionActifactSourceInformation(401, 140, 102);
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(false);

        self::assertNull($this->action_builder->buildTopBacklogActionBuilder($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoActionIsBuiltForArtifactsThatAreNotPlannable(): void
    {
        $source_information = new TopBacklogActionActifactSourceInformation(2, 140, 102);
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(false);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(false);

        self::assertNull($this->action_builder->buildTopBacklogActionBuilder($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoActionIsBuiltForArtifactsThatArePlannedInAProgramIncrement(): void
    {
        $source_information = new TopBacklogActionActifactSourceInformation(3, 140, 102);
        $this->build_program->shouldReceive('buildExistingProgramProject')->andReturn(new Program(102));
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->artifacts_explicit_top_backlog_dao->shouldReceive('isInTheExplicitTopBacklog')->andReturn(false);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(true);
        $this->planned_feature_dao->shouldReceive('isFeaturePlannedInAProgramIncrement')->andReturn(true);

        self::assertNull($this->action_builder->buildTopBacklogActionBuilder($source_information, UserTestBuilder::aUser()->build()));
    }
}
