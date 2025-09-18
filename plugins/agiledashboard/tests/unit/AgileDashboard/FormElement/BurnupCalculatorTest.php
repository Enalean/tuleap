<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Test\Builders\FormElement;

use Override;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\FormElement\Burnup\Calculator\RetrieveBurnupEffortForArtifact;
use Tuleap\AgileDashboard\FormElement\BurnupCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupDataDAO;
use Tuleap\AgileDashboard\FormElement\BurnupEffort;
use Tuleap\AgileDashboard\Stub\FormElement\Burnup\Claculator\RetrieveBurnupEffortForArtifactStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BurnupCalculatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BurnupCalculator $burnup_calculator;
    private Tracker_ArtifactFactory&\PHPUnit\Framework\MockObject\MockObject $artifact_factory_mock;
    private BurnupDataDAO&\PHPUnit\Framework\MockObject\MockObject $burnup_dao_mock;
    private RetrieveBurnupEffortForArtifact $artifact_effort_calculator;
    private BurnupEffort $burnup_effort;

    #[Override]
    protected function setUp(): void
    {
        $this->artifact_factory_mock      = $this->createMock(Tracker_ArtifactFactory::class);
        $this->burnup_dao_mock            = $this->createMock(BurnupDataDAO::class);
        $this->burnup_effort              = new BurnupEffort(5.0, 10.0);
        $this->artifact_effort_calculator = RetrieveBurnupEffortForArtifactStub::withEffort($this->burnup_effort);

        $this->burnup_calculator = new BurnupCalculator(
            $this->artifact_factory_mock,
            $this->burnup_dao_mock,
            $this->artifact_effort_calculator
        );
    }

    public function testGetValueReturnsEffortWithNoArtifacts(): void
    {
        $artifact_id          = 1;
        $timestamp            = 1000;
        $backlog_trackers_ids = [101, 102];

        $this->burnup_dao_mock
            ->expects($this->once())
            ->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with($artifact_id, $timestamp, $backlog_trackers_ids)
            ->willReturn([]);

        $result = $this->burnup_calculator->getValue($artifact_id, $timestamp, $backlog_trackers_ids);

        $this->assertEquals(0.0, $result->getTeamEffort());
        $this->assertEquals(0.0, $result->getTotalEffort());
    }

    public function testGetValueReturnsEffortWithValidArtifacts(): void
    {
        $artifact_id          = 1;
        $timestamp            = 1000;
        $backlog_trackers_ids = [101, 102];

        $backlog_items = [
            ['id' => 201],
            ['id' => 202],
        ];

        $artifact_1 = ArtifactTestBuilder::anArtifact(201)->build();


        $this->burnup_dao_mock
            ->expects($this->once())
            ->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with($artifact_id, $timestamp, $backlog_trackers_ids)
            ->willReturn($backlog_items);

        $this->artifact_factory_mock
            ->method('getArtifactById')
            ->willReturnMap([
                [201, $artifact_1],
            ]);


        $result = $this->burnup_calculator->getValue($artifact_id, $timestamp, $backlog_trackers_ids);

        $this->assertEquals($this->burnup_effort->getTeamEffort(), $result->getTeamEffort());
        $this->assertEquals($this->burnup_effort->getTotalEffort(), $result->getTotalEffort());
    }

    public function testGetValueIgnoresInvalidArtifacts(): void
    {
        $artifact_id          = 1;
        $timestamp            = 1000;
        $backlog_trackers_ids = [101, 102];

        $backlog_items = [
            ['id' => 201],
            ['id' => 202],
        ];

        $this->burnup_dao_mock
            ->expects($this->once())
            ->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with($artifact_id, $timestamp, $backlog_trackers_ids)
            ->willReturn($backlog_items);

        $this->artifact_factory_mock
            ->expects($this->exactly(2))
            ->method('getArtifactById')
            ->willReturnMap([
                [201, null],
            ]);

        $result = $this->burnup_calculator->getValue($artifact_id, $timestamp, $backlog_trackers_ids);

        $this->assertEquals(0.0, $result->getTeamEffort());
        $this->assertEquals(0.0, $result->getTotalEffort());
    }
}
