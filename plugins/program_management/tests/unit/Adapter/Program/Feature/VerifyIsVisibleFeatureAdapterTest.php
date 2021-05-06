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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class VerifyIsVisibleFeatureAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var VerifyIsVisibleFeature */
    private $verifier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_factory = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->verifier         = new VerifyIsVisibleFeatureAdapter(
            $this->artifact_factory
        );
    }

    public function testReturnsFalseIfFeatureArtifactCannotBeFoundOrUserCantViewIt(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->once()->with($user, 404)->andReturnNull();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);

        self::assertFalse($this->verifier->isVisibleFeature(404, $user, $program));
    }

    public function testReturnsFalseIfFeatureDoesNotBelongToGivenProgram(): void
    {
        $user     = UserTestBuilder::aUser()->withId(107)->build();
        $artifact = $this->buildFeatureArtifact(741, 110);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->once()
            ->with($user, $artifact->getId())
            ->andReturn($artifact);
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 404, $user);

        self::assertFalse($this->verifier->isVisibleFeature(741, $user, $program));
    }

    public function testReturnsTrue(): void
    {
        $user     = UserTestBuilder::aUser()->withId(107)->build();
        $artifact = $this->buildFeatureArtifact(741, 110);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->once()
            ->with($user, $artifact->getId())
            ->andReturn($artifact);
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);

        self::assertTrue($this->verifier->isVisibleFeature(741, $user, $program));
    }

    private function buildFeatureArtifact(int $artifact_id, int $project_id): Artifact
    {
        $project  = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $tracker  = TrackerTestBuilder::aTracker()->withId(76)->withProject($project)->build();
        $artifact = new Artifact($artifact_id, $tracker->getId(), 101, 1234567890, false);
        $artifact->setTracker($tracker);
        return $artifact;
    }
}
