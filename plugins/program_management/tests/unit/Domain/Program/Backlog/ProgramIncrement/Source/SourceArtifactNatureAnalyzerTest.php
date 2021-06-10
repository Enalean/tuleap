<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\SourceArtifactNatureAnalyzer;
use Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes\MirroredTimeboxesDao;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TimeboxOfMirroredTimeboxNotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SourceArtifactNatureAnalyzerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|MirroredTimeboxesDao
     */
    private $mirrored_timeboxes_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    private SourceArtifactNatureAnalyzer $analyser;
    private \PFUser $user;
    private \Project $project;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::aUser()->build();
        $this->project = \Project::buildForTest();

        $this->mirrored_timeboxes_dao = $this->createMock(MirroredTimeboxesDao::class);
        $this->artifact_factory       = $this->createMock(Tracker_ArtifactFactory::class);

        $this->analyser = new SourceArtifactNatureAnalyzer(
            $this->mirrored_timeboxes_dao,
            $this->artifact_factory
        );
    }

    public function testItThrowsExceptionWhenProgramIncrementIdIsNotFound(): void
    {
        $mirrored_timebox = $this->getMirroredTimebox();

        $this->mirrored_timeboxes_dao->method('getTimeboxFromMirroredTimeboxId')->willReturn(null);

        $this->expectException(TimeboxOfMirroredTimeboxNotFoundException::class);
        $this->analyser->retrieveProjectOfMirroredArtifact($mirrored_timebox, $this->user);
    }

    public function testItThrowsExceptionWhenProgramIncrementIsNotFound(): void
    {
        $mirrored_timebox = $this->getMirroredTimebox();

        $this->mirrored_timeboxes_dao->method('getTimeboxFromMirroredTimeboxId')->willReturn(100);
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(TimeboxOfMirroredTimeboxNotFoundException::class);
        $this->analyser->retrieveProjectOfMirroredArtifact($mirrored_timebox, $this->user);
    }

    public function testReturnsProjectWhenArtifactHaveMirroredMilestoneLink(): void
    {
        $mirrored_timebox = $this->getMirroredTimebox();

        $timebox = $this->getTimebox(true);
        $this->artifact_factory->method('getArtifactById')->willReturn($timebox);

        $this->mirrored_timeboxes_dao->method('getTimeboxFromMirroredTimeboxId')->willReturn(200);

        self::assertEquals($this->project, $this->analyser->retrieveProjectOfMirroredArtifact($mirrored_timebox, $this->user));
    }

    public function testItThrowsExceptionWhenUserCanNotSeeArtifact(): void
    {
        $timebox = $this->getTimebox(false);
        $this->artifact_factory->method('getArtifactById')->willReturn($timebox);

        $mirrored_timebox = $this->getMirroredTimebox();

        $this->mirrored_timeboxes_dao->expects(self::once())->method('getTimeboxFromMirroredTimeboxId')->willReturn(200);

        $this->expectException(TimeboxOfMirroredTimeboxNotFoundException::class);
        $this->analyser->retrieveProjectOfMirroredArtifact($mirrored_timebox, $this->user);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub|Artifact
     */
    private function getMirroredTimebox()
    {
        $mirrored_timebox = $this->createStub(Artifact::class);
        $mirrored_timebox->method('getId')->willReturn(1);
        $mirrored_timebox->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withId(12)->build());

        return $mirrored_timebox;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\Stub|Artifact
     */
    private function getTimebox(bool $user_can_view)
    {
        $timebox = $this->createStub(Artifact::class);
        $timebox->method('userCanView')->willReturn($user_can_view);
        $timebox->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withProject($this->project)->build());

        return $timebox;
    }
}
