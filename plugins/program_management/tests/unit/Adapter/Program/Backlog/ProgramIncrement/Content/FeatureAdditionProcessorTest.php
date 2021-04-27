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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content\AddFeature;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content\AddFeatureException;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\Content\FeatureAddition;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\VerifyCanBePlannedInProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class FeatureAdditionProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AddFeature
     */
    private $processor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactLinkUpdater
     */
    private $artifact_link_updater;

    protected function setUp(): void
    {
        $this->artifact_factory      = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->artifact_link_updater = \Mockery::mock(ArtifactLinkUpdater::class);
        $this->processor             = new FeatureAdditionProcessor(
            $this->artifact_factory,
            $this->artifact_link_updater
        );
    }

    public function testItThrowsWhenProgramIncrementArtifactCannotBeFound(): void
    {
        $feature_addition = $this->buildFeatureAddition();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(37)->andReturnNull();

        $this->expectException(ProgramIncrementNotFoundException::class);
        $this->processor->add($feature_addition);
    }

    public function dataProviderExceptions(): array
    {
        return [
            'it wraps Tracker_Exception'                    => [new \Tracker_Exception()],
            'it wraps Tracker_NoArtifactLinkFieldException' => [new \Tracker_NoArtifactLinkFieldException()],
        ];
    }

    /**
     * @dataProvider dataProviderExceptions
     */
    public function testItWrapsExceptions(\Throwable $exception): void
    {
        $feature_addition           = $this->buildFeatureAddition();
        $program_increment_artifact = new Artifact(37, 7, 110, 1234567890, false);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(37)->andReturn($program_increment_artifact);
        $this->artifact_link_updater->shouldReceive('updateArtifactLinks')->andThrow($exception);

        $this->expectException(AddFeatureException::class);
        $this->processor->add($feature_addition);
    }

    public function testItUpdatesArtifactLinksToAddFeatureToProgramIncrement(): void
    {
        $program                    = new Program(110);
        $user                       = UserTestBuilder::aUser()->build();
        $program_increment          = ProgramIncrementIdentifier::fromId(new CheckProgramIncrementStub(true), 37, $user);
        $feature                    = FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(), 76, $user, $program);
        $feature_addition           = FeatureAddition::fromFeature(
            new VerifyCanBePlannedInProgramIncrementStub(),
            $feature,
            $program_increment,
            $user
        );
        $program_increment_artifact = new Artifact(37, 7, 110, 1234567890, false);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(37)->andReturn($program_increment_artifact);
        $this->artifact_link_updater->shouldReceive('updateArtifactLinks')
            ->once()
            ->with($user, $program_increment_artifact, [76], [], \Tracker_FormElement_Field_ArtifactLink::NO_NATURE);

        $this->processor->add($feature_addition);
    }

    private function buildFeatureAddition(): FeatureAddition
    {
        $program           = new Program(110);
        $user              = UserTestBuilder::aUser()->build();
        $program_increment = ProgramIncrementIdentifier::fromId(new CheckProgramIncrementStub(true), 37, $user);
        $feature           = FeatureIdentifier::fromId(new VerifyIsVisibleFeatureStub(), 76, $user, $program);
        return FeatureAddition::fromFeature(
            new VerifyCanBePlannedInProgramIncrementStub(),
            $feature,
            $program_increment,
            $user
        );
    }
}
