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
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsPlannableStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MassChangeTopBacklogActionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BuildProgram $build_program;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;
    private VerifyIsPlannable $verify_is_plannable;
    private VerifyPrioritizeFeaturesPermissionStub $prioritize_verifier;

    protected function setUp(): void
    {
        $this->build_program       = BuildProgramStub::stubValidProgram();
        $this->prioritize_verifier = VerifyPrioritizeFeaturesPermissionStub::canPrioritize();

        $this->verify_is_plannable = VerifyIsPlannableStub::buildPlannableElement();
        $this->user                = $this->createMock(\PFUser::class);
        $this->user->method('isSuperUser')->willReturn(true);
        $this->user->method('isAdmin')->willReturn(true);
        $this->user->method('getId')->willReturn(101);
        $this->user->method('getUserName')->willReturn('John');
    }

    private function getBuilder(): MassChangeTopBacklogActionBuilder
    {
        return new MassChangeTopBacklogActionBuilder(
            $this->build_program,
            $this->prioritize_verifier,
            $this->verify_is_plannable,
            new class extends \TemplateRenderer {
                public function renderToString($template_name, $presenter): string
                {
                    return 'Rendered template';
                }
            }
        );
    }

    public function testMassChangeTopBacklogActionCanBeProvidedWhenUserHasPermissionInAAppropriateTracker(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();

        self::assertNotNull($this->getBuilder()->buildMassChangeAction($source_information, $this->user));
    }

    public function testNoMassChangeTopBacklogActionWhenTheProjectIsNotAProgram(): void
    {
        $source_information  = new TopBacklogActionMassChangeSourceInformation(240, 200);
        $this->build_program = BuildProgramStub::stubInvalidProgram();

        self::assertNull($this->getBuilder()->buildMassChangeAction($source_information, $this->user));
    }

    public function testNoMassChangeTopBacklogActionWhenTheUserCannotPrioritizeFeatures(): void
    {
        $source_information        = new TopBacklogActionMassChangeSourceInformation(403, 102);
        $this->build_program       = BuildProgramStub::stubValidProgram();
        $this->prioritize_verifier = VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize();

        self::assertNull($this->getBuilder()->buildMassChangeAction($source_information, $this->user));
    }

    public function testNoMassChangeTopBacklogActionWhenTheTrackerDoesNotContainsFeatures(): void
    {
        $source_information        = new TopBacklogActionMassChangeSourceInformation(600, 102);
        $this->build_program       = BuildProgramStub::stubValidProgram();
        $this->verify_is_plannable = VerifyIsPlannableStub::buildNotPlannableElement();

        self::assertNull($this->getBuilder()->buildMassChangeAction($source_information, $this->user));
    }
}
