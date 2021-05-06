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
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class MassChangeTopBacklogActionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
     * @var MassChangeTopBacklogActionBuilder
     */
    private $action_builder;

    protected function setUp(): void
    {
        $this->build_program                           = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier = \Mockery::mock(PrioritizeFeaturesPermissionVerifier::class);
        $this->plan_store                              = \Mockery::mock(PlanStore::class);
    }

    public function testMassChangeTopBacklogActionCanBeProvidedWhenUserHasPermissionInAAppropriateTracker(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(true);

        self::assertNotNull($this->getBuild()->buildMassChangeAction($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoMassChangeTopBacklogActionWhenTheProjectIsNotAProgram(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(240, 200);
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        self::assertNull($this->getBuild()->buildMassChangeAction($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoMassChangeTopBacklogActionWhenTheUserCannotPrioritizeFeatures(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(403, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(false);

        self::assertNull($this->getBuild()->buildMassChangeAction($source_information, UserTestBuilder::aUser()->build()));
    }

    public function testNoMassChangeTopBacklogActionWhenTheTrackerDoesNotContainsFeatures(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(600, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->prioritize_features_permission_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $this->plan_store->shouldReceive('isPlannable')->andReturn(false);

        self::assertNull($this->getBuild()->buildMassChangeAction($source_information, UserTestBuilder::aUser()->build()));
    }

    private function getBuild(): MassChangeTopBacklogActionBuilder
    {
        return new MassChangeTopBacklogActionBuilder(
            $this->build_program,
            $this->prioritize_features_permission_verifier,
            $this->plan_store,
            new class extends \TemplateRenderer
            {
                public function renderToString($template_name, $presenter): string
                {
                    return 'Rendered template';
                }
            }
        );
    }
}
