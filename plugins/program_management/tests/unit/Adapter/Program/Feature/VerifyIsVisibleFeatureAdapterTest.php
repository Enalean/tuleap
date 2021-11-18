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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class VerifyIsVisibleFeatureAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FEATURE_ID = 741;
    private const PROGRAM_ID = 110;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    private UserIdentifier $user_identifier;
    private \PFUser $user;
    private ProgramIdentifier $program;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->user             = UserTestBuilder::aUser()->build();
        $this->user_identifier  = UserIdentifierStub::buildGenericUser();
        $this->program          = ProgramIdentifierBuilder::buildWithId(self::PROGRAM_ID);
    }

    private function getVerifier(): VerifyIsVisibleFeatureAdapter
    {
        return new VerifyIsVisibleFeatureAdapter($this->artifact_factory, RetrieveUserStub::withUser($this->user));
    }

    public function testReturnsFalseIfFeatureArtifactCannotBeFoundOrUserCantViewIt(): void
    {
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactByIdUserCanView')
            ->with($this->user, 404)
            ->willReturn(null);

        self::assertFalse($this->getVerifier()->isVisibleFeature(404, $this->user_identifier, $this->program, null));
    }

    public function testReturnsFalseIfFeatureDoesNotBelongToGivenProgram(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);
        $program = ProgramIdentifierBuilder::buildWithId(404);

        self::assertFalse(
            $this->getVerifier()->isVisibleFeature(self::FEATURE_ID, $this->user_identifier, $program, null)
        );
    }

    public function testReturnsTrueWhenFeatureIsVisible(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        self::assertTrue(
            $this->getVerifier()->isVisibleFeature(self::FEATURE_ID, $this->user_identifier, $this->program, null)
        );
    }

    public function testItReturnsTrueWithBypassAndArtifactFromTheSameProgram(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->expects(self::once())
            ->method('getArtifactById')
            ->with(self::FEATURE_ID)
            ->willReturn($artifact);

        self::assertTrue(
            $this->getVerifier()->isVisibleFeature(
                self::FEATURE_ID,
                $this->user_identifier,
                $this->program,
                new WorkflowUserPermissionBypass()
            )
        );
    }

    public function testItReturnsFalseWithBypassAndArtifactCantBeFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        self::assertFalse(
            $this->getVerifier()->isVisibleFeature(
                self::FEATURE_ID,
                $this->user_identifier,
                $this->program,
                new WorkflowUserPermissionBypass()
            )
        );
    }

    public function testItReturnsFalseWithBypassAndArtifactFromOtherProject(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);
        $program = ProgramIdentifierBuilder::buildWithId(404);

        self::assertFalse(
            $this->getVerifier()->isVisibleFeature(
                self::FEATURE_ID,
                $this->user_identifier,
                $program,
                new WorkflowUserPermissionBypass()
            )
        );
    }

    public function testReturnsFalseWhenUserCanNotViewFeature(): void
    {
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn(null);

        self::assertFalse(
            $this->getVerifier()->isVisible(self::FEATURE_ID, $this->user_identifier)
        );
    }

    public function testReturnsTrueWhenUserCanViewFeature(): void
    {
        $artifact = $this->buildFeatureArtifact();
        $this->artifact_factory->method('getArtifactByIdUserCanView')->willReturn($artifact);

        self::assertTrue(
            $this->getVerifier()->isVisible(self::FEATURE_ID, $this->user_identifier)
        );
    }

    private function buildFeatureArtifact(): Artifact
    {
        $project  = ProjectTestBuilder::aProject()->withId(self::PROGRAM_ID)->build();
        $tracker  = TrackerTestBuilder::aTracker()->withId(76)->withProject($project)->build();
        $artifact = new Artifact(self::FEATURE_ID, $tracker->getId(), 101, 1234567890, false);
        $artifact->setTracker($tracker);
        return $artifact;
    }
}
