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

namespace Tuleap\Tracker\REST\Helpers;

use EventManager;
use Luracast\Restler\RestException;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tracker_Artifact_PriorityHistoryChange;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Event\ArtifactsReordered;
use Tuleap\Tracker\Artifact\PriorityManager;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactsRankOrdererTest extends TestCase
{
    private ArtifactsRankOrderer $orderer;
    private Project $project;
    private string $context_id;
    private PriorityManager&MockObject $priority_manager;
    private EventManager $event_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->project          = ProjectTestBuilder::aProject()->build();
        $this->context_id       = Tracker_Artifact_PriorityHistoryChange::NO_CONTEXT;
        $this->priority_manager = $this->createMock(PriorityManager::class);
        $this->event_manager    = $this->createMock(EventManager::class);
        $this->orderer          = new ArtifactsRankOrderer($this->priority_manager, $this->event_manager);
    }

    public function testReorderThrowsWhenSameIdIsPassedInOrderAndComparedTo(): void
    {
        $order = OrderRepresentation::build([123], OrderRepresentation::BEFORE, 123);

        $this->priority_manager->expects($this->once())
            ->method('moveListOfArtifactsBefore')
            ->with([123], 123, $this->context_id, 101)
            ->willThrowException(new Tracker_Artifact_Exception_CannotRankWithMyself(123));
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->orderer->reorder($order, $this->context_id, $this->project);
    }

    public function testReorderBeforeAnArtifact(): void
    {
        $order = OrderRepresentation::build([123, 789], OrderRepresentation::BEFORE, 456);

        $this->priority_manager->expects($this->once())
            ->method('moveListOfArtifactsBefore')
            ->with([123, 789], 456, $this->context_id, 101);
        $this->event_manager->method('processEvent')->willReturnCallback(
            function ($hook) {
                self::assertInstanceOf(ArtifactsReordered::class, $hook);
            }
        );

        $this->orderer->reorder($order, $this->context_id, $this->project);
    }

    public function testReorderAfterAnArtifact(): void
    {
        $order = OrderRepresentation::build([123, 789], OrderRepresentation::AFTER, 456);

        $this->priority_manager->expects($this->once())
            ->method('moveListOfArtifactsAfter')
            ->with([123, 789], 456, $this->context_id, 101);
        $this->event_manager->method('processEvent')->willReturnCallback(
            function ($hook) {
                self::assertInstanceOf(ArtifactsReordered::class, $hook);
            }
        );

        $this->orderer->reorder($order, $this->context_id, $this->project);
    }
}
