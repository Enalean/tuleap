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
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\Feature\VerifyIsVisibleFeature;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class VerifyIsVisibleFeatureAdapterTest extends TestCase
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
        $feature = new FeatureIdentifier(404);
        $program = new Program(110);

        self::assertFalse($this->verifier->isVisibleFeature($feature, $user, $program));
    }

    public function testReturnsFalseIfFeatureDoesNotBelongToGivenProgram(): void
    {
        $user     = UserTestBuilder::aUser()->withId(107)->build();
        $artifact = $this->buildFeatureArtifact(741, 110);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->once()
            ->with($user, $artifact->getId())
            ->andReturn($artifact);
        $feature = new FeatureIdentifier($artifact->getId());
        $program = new Program(404);

        self::assertFalse($this->verifier->isVisibleFeature($feature, $user, $program));
    }

    public function testReturnsTrue(): void
    {
        $user     = UserTestBuilder::aUser()->withId(107)->build();
        $artifact = $this->buildFeatureArtifact(741, 110);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')
            ->once()
            ->with($user, $artifact->getId())
            ->andReturn($artifact);
        $feature = new FeatureIdentifier($artifact->getId());
        $program = new Program(110);

        self::assertTrue($this->verifier->isVisibleFeature($feature, $user, $program));
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
