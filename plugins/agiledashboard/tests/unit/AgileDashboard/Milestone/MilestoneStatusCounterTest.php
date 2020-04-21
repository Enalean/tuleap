<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

class AgileDashboard_Milestone_MilestoneStatusCounterTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var AgileDashboard_Milestone_MilestoneStatusCounter
     */
    private $counter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactDao
     */
    private $artifact_dao;

    /**
     * @var AgileDashboard_BacklogItemDao|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backlog_dao;

    protected function setUp(): void
    {
        $this->backlog_dao      = \Mockery::spy(\AgileDashboard_BacklogItemDao::class);
        $this->artifact_dao     = \Mockery::spy(\Tracker_ArtifactDao::class);
        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->user             = \Mockery::spy(PFUser::class);
        $this->counter          = new AgileDashboard_Milestone_MilestoneStatusCounter(
            $this->backlog_dao,
            $this->artifact_dao,
            $this->artifact_factory
        );

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('userCanView')->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->andReturns(
            $artifact
        );
    }

    public function testItDoesntFetchAnythingWhenNoMilestoneId(): void
    {
        $this->backlog_dao->shouldReceive('getBacklogArtifacts')->never();
        $result = $this->counter->getStatus($this->user, null);
        $this->assertEquals(
            [
                Tracker_Artifact::STATUS_OPEN   => 0,
                Tracker_Artifact::STATUS_CLOSED => 0,
            ],
            $result
        );
    }

    public function testItReturnsZeroOpenClosedWhenNoArtifacts(): void
    {
        $this->backlog_dao->shouldReceive('getBacklogArtifacts')->with(12)->andReturns(\TestHelper::emptyDar());
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals(
            [
                Tracker_Artifact::STATUS_OPEN   => 0,
                Tracker_Artifact::STATUS_CLOSED => 0,
            ],
            $result
        );
    }

    public function testItDoesntTryToFetchChildrenWhenNoBacklog(): void
    {
        $this->backlog_dao->shouldReceive('getBacklogArtifacts')->andReturns(\TestHelper::emptyDar());
        $this->artifact_dao->shouldReceive('getChildrenForArtifacts')->never();
        $this->counter->getStatus($this->user, 12);
    }

    public function testItFetchesTheStatusOfReturnedArtifacts(): void
    {
        $this->backlog_dao->shouldReceive('getBacklogArtifacts')->with(12)->andReturns(
            \TestHelper::arrayToDar(['id' => 35], ['id' => 36])
        );
        $this->artifact_dao->shouldReceive('getArtifactsStatusByIds')->with([35, 36])->andReturns(
            \TestHelper::arrayToDar(
                ['id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN],
                ['id' => 35, 'status' => Tracker_Artifact::STATUS_CLOSED]
            )
        );
        $this->artifact_dao->shouldReceive('getChildrenForArtifacts')->andReturns(\TestHelper::emptyDar());
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals(
            [
                Tracker_Artifact::STATUS_OPEN   => 1,
                Tracker_Artifact::STATUS_CLOSED => 1,
            ],
            $result
        );
    }

    public function testItFetchesTheStatusOfReturnedArtifactsAtSublevel(): void
    {
        // Level 0
        $this->backlog_dao->shouldReceive('getBacklogArtifacts')->with(12)->andReturns(
            \TestHelper::arrayToDar(['id' => 35], ['id' => 36])
        );
        $this->artifact_dao->shouldReceive('getArtifactsStatusByIds')->with([35, 36])->andReturns(
            \TestHelper::arrayToDar(
                ['id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN],
                ['id' => 35, 'status' => Tracker_Artifact::STATUS_CLOSED]
            )
        );

        // Level -1
        $this->artifact_dao->shouldReceive('getChildrenForArtifacts')->with([35, 36])->andReturns(
            \TestHelper::arrayToDar(['id' => 38], ['id' => 39], ['id' => 40])
        );
        $this->artifact_dao->shouldReceive('getArtifactsStatusByIds')->with([38, 39, 40])->andReturns(
            \TestHelper::arrayToDar(
                ['id' => 38, 'status' => Tracker_Artifact::STATUS_OPEN],
                ['id' => 39, 'status' => Tracker_Artifact::STATUS_CLOSED],
                ['id' => 40, 'status' => Tracker_Artifact::STATUS_CLOSED]
            )
        );

        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals(
            [
                Tracker_Artifact::STATUS_OPEN   => 2,
                Tracker_Artifact::STATUS_CLOSED => 3,
            ],
            $result
        );
    }

    public function testItDoesntCountBacklogElementNotReadable(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('userCanView')->andReturnFalse();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(35)->andReturns(
            $artifact
        );
        $other_artifact = Mockery::mock(Tracker_Artifact::class);
        $other_artifact->shouldReceive('userCanView')->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(36)->andReturns(
            $other_artifact
        );

        $this->backlog_dao->shouldReceive('getBacklogArtifacts')->with(12)->andReturns(
            \TestHelper::arrayToDar(['id' => 35], ['id' => 36])
        );
        $this->artifact_dao->shouldReceive('getArtifactsStatusByIds')->andReturns(
            \TestHelper::arrayToDar(['id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN])
        );
        $this->artifact_dao->shouldReceive('getChildrenForArtifacts')->andReturns(\TestHelper::emptyDar());
        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals(
            [
                Tracker_Artifact::STATUS_OPEN   => 1,
                Tracker_Artifact::STATUS_CLOSED => 0,
            ],
            $result
        );
    }

    public function testItDoesntCountSubElementsNotReadable(): void
    {
        $artifact_36 = Mockery::mock(Tracker_Artifact::class);
        $artifact_36->shouldReceive('userCanView')->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(36)->andReturns(
            $artifact_36
        );

        $artifact_37 = Mockery::mock(Tracker_Artifact::class);
        $artifact_37->shouldReceive('userCanView')->andReturnFalse();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(37)->andReturns(
            $artifact_37
        );

        $artifact_38 = Mockery::mock(Tracker_Artifact::class);
        $artifact_38->shouldReceive('userCanView')->andReturnTrue();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(38)->andReturns(
            $artifact_38
        );

        $this->backlog_dao->shouldReceive('getBacklogArtifacts')->with(12)->andReturns(
            \TestHelper::arrayToDar(['id' => 36])
        );
        $this->artifact_dao->shouldReceive('getArtifactsStatusByIds')->with([36])->andReturns(
            \TestHelper::arrayToDar(['id' => 36, 'status' => Tracker_Artifact::STATUS_OPEN])
        );
        $this->artifact_dao->shouldReceive('getChildrenForArtifacts')->andReturns(
            \TestHelper::arrayToDar(['id' => 37], ['id' => 38])
        );

        $this->artifact_dao->shouldReceive('getArtifactsStatusByIds')->with([37, 38])->andReturns(
            \TestHelper::arrayToDar(['id' => 38, 'status' => Tracker_Artifact::STATUS_OPEN])
        );

        $result = $this->counter->getStatus($this->user, 12);
        $this->assertEquals(
            [
                Tracker_Artifact::STATUS_OPEN   => 2,
                Tracker_Artifact::STATUS_CLOSED => 0,
            ],
            $result
        );
    }
}
