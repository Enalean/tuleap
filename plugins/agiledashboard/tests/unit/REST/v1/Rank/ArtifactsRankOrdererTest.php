<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Rank;

use Luracast\Restler\RestException;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tuleap\AgileDashboard\REST\v1\OrderRepresentation;
use Tuleap\Tracker\Artifact\Event\ArtifactsReordered;

final class ArtifactsRankOrdererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ArtifactsRankOrderer */
    private $orderer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Project
     */
    private $project;
    /**
     * @var string
     */
    private $context_id;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Artifact_PriorityManager
     */
    private $priority_manager;
    /**
     * @var \EventManager|M\LegacyMockInterface|M\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->project          = M::mock(\Project::class)->shouldReceive(['getID' => 101])->getMock();
        $this->context_id       = \Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT;
        $this->priority_manager = M::mock(\Tracker_Artifact_PriorityManager::class);
        $this->priority_manager->shouldReceive('enableExceptionsOnError');
        $this->event_manager = M::mock(\EventManager::class);
        $this->orderer       = new ArtifactsRankOrderer($this->priority_manager, $this->event_manager);
    }

    public function testReorderThrowsWhenSameIdIsPassedInOrderAndComparedTo(): void
    {
        $order = OrderRepresentation::build([123], OrderRepresentation::BEFORE, 123);

        $this->priority_manager->shouldReceive('moveListOfArtifactsBefore')
            ->with([123], 123, $this->context_id, 101)
            ->once()
            ->andThrow(new Tracker_Artifact_Exception_CannotRankWithMyself(123));
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->orderer->reorder($order, $this->context_id, $this->project);
    }

    public function testReorderBeforeAnArtifact(): void
    {
        $order = OrderRepresentation::build([123, 789], OrderRepresentation::BEFORE, 456);

        $this->priority_manager->shouldReceive('moveListOfArtifactsBefore')
            ->with([123, 789], 456, $this->context_id, 101)
            ->once();
        $this->event_manager->shouldReceive('processEvent')->with(
            M::on(
                function ($hook) {
                    return $hook instanceof ArtifactsReordered;
                }
            )
        )->once();

        $this->orderer->reorder($order, $this->context_id, $this->project);
    }

    public function testReorderAfterAnArtifact(): void
    {
        $order = OrderRepresentation::build([123, 789], OrderRepresentation::AFTER, 456);

        $this->priority_manager->shouldReceive('moveListOfArtifactsAfter')
            ->with([123, 789], 456, $this->context_id, 101)
            ->once();
        $this->event_manager->shouldReceive('processEvent')->with(
            M::on(
                function ($hook) {
                    return $hook instanceof ArtifactsReordered;
                }
            )
        )->once();

        $this->orderer->reorder($order, $this->context_id, $this->project);
    }
}
