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

use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\FeaturesToReorderProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureCanNotBeRankedWithItselfException;
use Tuleap\ProgramManagement\REST\v1\FeatureElementToOrderInvolvedInChangeRepresentation;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FeaturesRankOrdererTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FeaturesRankOrderer $orderer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Tracker\Artifact\PriorityManager
     */
    private $priority_manager;

    protected function setUp(): void
    {
        $this->priority_manager = $this->createMock(\Tuleap\Tracker\Artifact\PriorityManager::class);

        $this->orderer = new FeaturesRankOrderer($this->priority_manager);
    }

    public function testThrowErrorIfLinkFieldIsNotAccessible(): void
    {
        $order           = new FeatureElementToOrderInvolvedInChangeRepresentation([111], 'before', 45);
        $feature_reorder = FeaturesToReorderProxy::buildFromRESTRepresentation($order);
        if (! $feature_reorder) {
            throw new \LogicException('Feature reorder is not defined');
        }

        $this->priority_manager
            ->expects($this->once())
            ->method('moveListOfArtifactsBefore')
            ->with([111], 45, '101', 101)
            ->willThrowException(new Tracker_Artifact_Exception_CannotRankWithMyself(45));

        $this->expectException(FeatureCanNotBeRankedWithItselfException::class);
        $this->orderer->reorder($feature_reorder, '101', ProgramIdentifierBuilder::build());
    }
}
