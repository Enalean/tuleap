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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactLinkFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Tracker\Artifact\Artifact;

final class UserCanPlanInProgramIncrementVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_ID = 274;
    private UserIdentifierStub $user;
    private UserCanPrioritize $user_can_prioritize;
    private ProgramIncrementIdentifier $program_increment;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Artifact
     */
    private $artifact;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_FormElement_Field_ArtifactLink
     */
    private $field;
    private RetrieveFullArtifactLinkFieldStub $field_retriever;

    protected function setUp(): void
    {
        $this->user                = UserIdentifierStub::buildGenericUser();
        $this->user_can_prioritize = UserCanPrioritize::fromUser(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            $this->user,
            ProgramIdentifierBuilder::build(),
            null
        );
        $this->program_increment   = ProgramIncrementIdentifierBuilder::buildWithIdAndUser(
            self::PROGRAM_INCREMENT_ID,
            $this->user
        );
        $this->artifact            = $this->createStub(Artifact::class);

        $this->field           = $this->createStub(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->field_retriever = RetrieveFullArtifactLinkFieldStub::withField($this->field);
    }

    private function getVerifier(): UserCanPlanInProgramIncrementVerifier
    {
        return new UserCanPlanInProgramIncrementVerifier(
            RetrieveFullArtifactStub::withArtifact($this->artifact),
            RetrieveUserStub::withGenericUser(),
            RetrieveProgramIncrementTrackerStub::withValidTracker(16),
            $this->field_retriever
        );
    }

    public function testUserCanPlan(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);
        $this->field->method('userCanUpdate')->willReturn(true);

        self::assertTrue($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCannotPlanWhenUserCannotUpdateProgramIncrement(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(false);

        self::assertFalse($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCannotPlanWhenProgramIncrementTrackerHasNoArtifactLinkField(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);
        $this->field_retriever = RetrieveFullArtifactLinkFieldStub::withNoField();

        self::assertFalse($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCannotPlanWhenUserCannotUpdateArtifactLinkOfProgramIncrement(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);
        $this->field->method('userCanUpdate')->willReturn(false);

        self::assertFalse($this->getVerifier()->userCanPlan($this->program_increment, $this->user));
    }

    public function testUserCanPlanAndPrioritize(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);
        $this->field->method('userCanUpdate')->willReturn(true);

        self::assertTrue($this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize));
    }

    public function testUserCannotPlanAndPrioritizeWhenUserCannotUpdateProgramIncrement(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(false);

        self::assertFalse($this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize));
    }

    public function testUserCannotPlanAndPrioritizeWhenProgramIncrementTrackerHasNoArtifactLinkField(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);
        $this->field_retriever = RetrieveFullArtifactLinkFieldStub::withNoField();

        self::assertFalse($this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize));
    }

    public function testUserCannotPlanAndPrioritizeWhenUserCannotUpdateArtifactLinkOfProgramIncrement(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);
        $this->field->method('userCanUpdate')->willReturn(false);

        self::assertFalse($this->getVerifier()->userCanPlanAndPrioritize($this->program_increment, $this->user_can_prioritize));
    }
}
