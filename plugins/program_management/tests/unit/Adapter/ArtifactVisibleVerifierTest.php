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

namespace Tuleap\ProgramManagement\Adapter;

use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Domain\Permissions\PermissionBypass;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactVisibleVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID     = 110;
    private const ARTIFACT_ID = 618;
    private const PROGRAM_ID  = 148;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
    }

    private function getVerifier(): ArtifactVisibleVerifier
    {
        return new ArtifactVisibleVerifier(
            $this->artifact_factory,
            RetrieveUserStub::withUser(UserTestBuilder::buildWithId(self::USER_ID))
        );
    }

    private function isArtifactVisible(): bool
    {
        return $this->getVerifier()->isVisible(self::ARTIFACT_ID, UserIdentifierStub::withId(self::USER_ID));
    }

    public function testArtifactIsVisible(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build();
        $this->artifact_factory->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->willReturn($artifact);
        self::assertTrue($this->isArtifactVisible());
    }

    public function testArtifactIsNotVisible(): void
    {
        $this->artifact_factory->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->willReturn(null);
        self::assertFalse($this->isArtifactVisible());
    }

    private function isFeatureVisibleAndInProgram(?PermissionBypass $bypass): bool
    {
        return $this->getVerifier()->isFeatureVisibleAndInProgram(
            self::ARTIFACT_ID,
            UserIdentifierStub::withId(self::USER_ID),
            ProgramIdentifierBuilder::buildWithId(self::PROGRAM_ID),
            $bypass
        );
    }

    public function testReturnsFalseIfFeatureArtifactCannotBeFoundOrUserCantViewIt(): void
    {
        $this->artifact_factory->expects($this->once())
            ->method('getArtifactByIdUserCanView')
            ->willReturn(null);

        self::assertFalse($this->isFeatureVisibleAndInProgram(null));
    }

    public function testReturnsFalseIfFeatureDoesNotBelongToGivenProgram(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);
        $program = ProgramIdentifierBuilder::buildWithId(404);

        self::assertFalse(
            $this->getVerifier()->isFeatureVisibleAndInProgram(
                self::ARTIFACT_ID,
                UserIdentifierStub::withId(self::USER_ID),
                $program,
                null
            )
        );
    }

    public function testReturnsTrueWhenFeatureIsVisible(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        self::assertTrue($this->isFeatureVisibleAndInProgram(null));
    }

    public function testItReturnsTrueWithBypassAndArtifactFromTheSameProgram(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->expects($this->once())
            ->method('getArtifactById')
            ->with(self::ARTIFACT_ID)
            ->willReturn($artifact);

        self::assertTrue($this->isFeatureVisibleAndInProgram(new WorkflowUserPermissionBypass()));
    }

    public function testItReturnsFalseWithBypassAndArtifactCantBeFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);
        self::assertFalse($this->isFeatureVisibleAndInProgram(new WorkflowUserPermissionBypass()));
    }

    public function testItReturnsFalseWithBypassAndArtifactFromOtherProject(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);
        $program = ProgramIdentifierBuilder::buildWithId(404);

        self::assertFalse(
            $this->getVerifier()->isFeatureVisibleAndInProgram(
                self::ARTIFACT_ID,
                UserIdentifierStub::withId(self::USER_ID),
                $program,
                new WorkflowUserPermissionBypass()
            )
        );
    }

    private function buildFeatureArtifact(): Artifact
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROGRAM_ID)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(76)->withProject($project)->build();
        return ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker($tracker)->build();
    }

    private function isFeatureVisible(): bool
    {
        return $this->getVerifier()->isVisibleFeature(self::ARTIFACT_ID, UserIdentifierStub::withId(self::USER_ID));
    }

    public function testReturnsFalseWhenUserCanNotViewFeature(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);
        self::assertFalse($this->isFeatureVisible());
    }

    public function testReturnsTrueWhenUserCanViewFeature(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        self::assertTrue($this->isFeatureVisible());
    }

    private function isUserStoryVisible(): bool
    {
        return $this->getVerifier()->isUserStoryVisible(self::ARTIFACT_ID, UserIdentifierStub::withId(self::USER_ID));
    }

    public function testReturnsFalseWhenUserCannotSeeUserStory(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);
        self::assertFalse($this->isUserStoryVisible());
    }

    public function testReturnsTrueWhenUserCanSeeUserStory(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        self::assertTrue($this->isUserStoryVisible());
    }
}
