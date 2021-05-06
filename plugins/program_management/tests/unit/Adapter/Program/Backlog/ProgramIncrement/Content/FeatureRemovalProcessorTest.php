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
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeatureRemoval;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\RemoveFeatureException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\UserCanPrioritize;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleFeatureStub;
use Tuleap\ProgramManagement\Stub\VerifyLinkedUserStoryIsNotPlannedStub;
use Tuleap\ProgramManagement\Stub\VerifyPrioritizeFeaturePermissionStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class FeatureRemovalProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RemoveFeature
     */
    private $processor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProgramIncrementsDAO
     */
    private $program_increments_dao;
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
        $this->program_increments_dao = \Mockery::mock(ProgramIncrementsDAO::class);
        $this->artifact_factory       = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->artifact_link_updater  = \Mockery::mock(ArtifactLinkUpdater::class);
        $this->processor              = new FeatureRemovalProcessor(
            $this->program_increments_dao,
            $this->artifact_factory,
            $this->artifact_link_updater
        );
    }

    public function testWhenThereAreNoProgramIncrementsLinkingTheFeatureItDoesNothing(): void
    {
        $this->program_increments_dao->shouldReceive('getProgramIncrementsLinkToFeatureId')
            ->once()
            ->andReturn([]);

        $feature_removal = $this->buildFeatureRemoval();
        $this->processor->removeFromAllProgramIncrements($feature_removal);

        $this->artifact_link_updater->shouldNotHaveReceived('updateArtifactLinks');
    }

    public function testItSkipsNonExistentProgramIncrements(): void
    {
        $program_increment_ids = [['id' => 404], ['id' => 405]];
        $this->program_increments_dao->shouldReceive('getProgramIncrementsLinkToFeatureId')
            ->once()
            ->andReturn($program_increment_ids);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(404)
            ->andReturnNull();

        $program_increment_artifact = new Artifact(405, 7, 101, 1234567890, false);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(405)
            ->andReturn($program_increment_artifact);

        $feature_removal = $this->buildFeatureRemoval();
        $this->artifact_link_updater->shouldReceive('updateArtifactLinks')
            ->once()
            ->with(
                $feature_removal->user->getFullUser(),
                $program_increment_artifact,
                [],
                [$feature_removal->feature_id],
                \Tracker_FormElement_Field_ArtifactLink::NO_NATURE
            );

        $this->processor->removeFromAllProgramIncrements($feature_removal);
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
        $program_increment_ids = [['id' => 25]];
        $this->program_increments_dao->shouldReceive('getProgramIncrementsLinkToFeatureId')
            ->andReturn($program_increment_ids);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(25)
            ->andReturn(new Artifact(25, 7, 101, 1234567890, false));
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(98)
            ->andReturn(new Artifact(98, 7, 101, 1234567890, false));
        $feature_removal = $this->buildFeatureRemoval();
        $this->artifact_link_updater->shouldReceive('updateArtifactLinks')->andThrow($exception);

        $this->expectException(RemoveFeatureException::class);
        $this->processor->removeFromAllProgramIncrements($feature_removal);
    }

    public function testItUpdatesArtifactLinksToRemoveFeatureFromAllProgramIncrements(): void
    {
        $program_increment_ids = [['id' => 25], ['id' => 98]];
        $this->program_increments_dao->shouldReceive('getProgramIncrementsLinkToFeatureId')
            ->andReturn($program_increment_ids);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(25)
            ->andReturn(new Artifact(25, 7, 101, 1234567890, false));
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(98)
            ->andReturn(new Artifact(98, 7, 101, 1234567890, false));

        $feature_removal = $this->buildFeatureRemoval();
        $this->artifact_link_updater->shouldReceive('updateArtifactLinks')->twice();

        $this->processor->removeFromAllProgramIncrements($feature_removal);
    }

    private function buildFeatureRemoval(): FeatureRemoval
    {
        $user    = UserTestBuilder::aUser()->build();
        $program = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 110, $user);
        $feature = FeatureIdentifier::fromId(VerifyIsVisibleFeatureStub::buildVisibleFeature(), 76, $user, $program);
        return FeatureRemoval::fromFeature(
            VerifyLinkedUserStoryIsNotPlannedStub::buildNotLinkedStories(),
            $feature,
            UserCanPrioritize::fromUser(VerifyPrioritizeFeaturePermissionStub::canPrioritize(), $user, $program)
        );
    }
}
