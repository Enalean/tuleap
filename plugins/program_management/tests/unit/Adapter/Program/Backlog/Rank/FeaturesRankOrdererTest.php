<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Rank;

use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;

final class FeaturesRankOrdererTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact_PriorityManager
     */
    private $priority_manager;
    /**
     * @var FeaturesRankOrderer
     */
    private $orderer;

    protected function setUp(): void
    {
        $this->priority_manager = \Mockery::mock(\Tracker_Artifact_PriorityManager::class);
        $this->priority_manager->shouldReceive('enableExceptionsOnError');

        $this->orderer = new FeaturesRankOrderer($this->priority_manager);
    }

    public function testThrowErrorIfLinkFieldIsNotAccessible(): void
    {
        $order              = new FeatureElementToOrderInvolvedInChangeRepresentation();
        $order->ids         = [111];
        $order->compared_to = 45;
        $order->direction   = "before";

        $this->priority_manager
            ->shouldReceive("moveListOfArtifactsBefore")
            ->with([111], 45, "101", 101)
            ->once()
            ->andThrow(new Tracker_Artifact_Exception_CannotRankWithMyself(45));

        $this->expectExceptionObject(new RestException(400, "Artifact 45 cannot be ranked with itself."));
        $this->orderer->reorder($order, "101", new Program(101));
    }
}
