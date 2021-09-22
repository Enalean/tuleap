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

use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogActionMassChangeSourceInformation;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;

final class MassChangeTopBacklogActionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BuildProgram $build_program;
    private MassChangeTopBacklogActionBuilder $action_builder;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PlanStore
     */
    private $plan_store;

    protected function setUp(): void
    {
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->plan_store    = $this->createMock(PlanStore::class);
        $this->user          = $this->createMock(\PFUser::class);
        $this->user->method('isSuperUser')->willReturn(true);
        $this->user->method('isAdmin')->willReturn(true);
        $this->user->method('getId')->willReturn(101);
        $this->user->method('getName')->willReturn('John');
    }

    public function testMassChangeTopBacklogActionCanBeProvidedWhenUserHasPermissionInAAppropriateTracker(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->plan_store->method('isPlannable')->willReturn(true);

        self::assertNotNull($this->getBuild(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildMassChangeAction($source_information, $this->user));
    }

    public function testNoMassChangeTopBacklogActionWhenTheProjectIsNotAProgram(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(240, 200);
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        self::assertNull($this->getBuild(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildMassChangeAction($source_information, $this->user));
    }

    public function testNoMassChangeTopBacklogActionWhenTheUserCannotPrioritizeFeatures(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(403, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();

        self::assertNull($this->getBuild(VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize())->buildMassChangeAction($source_information, $this->user));
    }

    public function testNoMassChangeTopBacklogActionWhenTheTrackerDoesNotContainsFeatures(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(600, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->plan_store->method('isPlannable')->willReturn(false);

        self::assertNull($this->getBuild(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildMassChangeAction($source_information, $this->user));
    }

    private function getBuild(VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier): MassChangeTopBacklogActionBuilder
    {
        return new MassChangeTopBacklogActionBuilder(
            $this->build_program,
            $prioritize_features_permission_verifier,
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
