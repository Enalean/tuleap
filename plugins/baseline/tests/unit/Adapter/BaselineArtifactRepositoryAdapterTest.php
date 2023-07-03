<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\Baseline\Support\DateTimeFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class BaselineArtifactRepositoryAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    private BaselineArtifactRepositoryAdapter $adapter;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Tracker_Artifact_ChangesetFactory&MockObject $changeset_factory;
    private SemanticValueAdapter&MockObject $semantic_value_adapter;
    private ArtifactLinkRepository&MockObject $artifact_link_adapter;

    /** @before */
    public function createInstance(): void
    {
        $this->artifact_factory       = $this->createMock(Tracker_ArtifactFactory::class);
        $this->changeset_factory      = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $this->semantic_value_adapter = $this->createMock(SemanticValueAdapter::class);
        $this->artifact_link_adapter  = $this->createMock(ArtifactLinkRepository::class);

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager
            ->method('getUserById')
            ->with($this->current_user->getId())
            ->willReturn($this->current_tuleap_user);

        $this->adapter = new BaselineArtifactRepositoryAdapter(
            $this->artifact_factory,
            $this->changeset_factory,
            $this->semantic_value_adapter,
            $this->artifact_link_adapter,
            $user_manager,
        );
    }

    public function testFindById(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(10)->withProject($project)->build();

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('userCanView')->willReturn(true);
        $artifact->method('getTracker')->willReturn($tracker);

        $this->artifact_factory
            ->method('getArtifactById')
            ->with(1)
            ->willReturn($artifact);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getArtifact')->willReturn($artifact);

        $this->changeset_factory
            ->method('getLastChangeset')
            ->with($artifact)
            ->willReturn($changeset);

        $this->mockSemanticValueAdapter($changeset);

        $this->artifact_link_adapter
            ->method('findLinkedArtifactIds')
            ->with($this->current_tuleap_user, $changeset)
            ->willReturn([2, 3, 4]);

        $baseline_artifact = $this->adapter->findById($this->current_user, 1);

        self::assertNotNull($baseline_artifact);
        self::assertEquals('Custom title', $baseline_artifact->getTitle());
        self::assertEquals('Custom description', $baseline_artifact->getDescription());
        self::assertEquals('Custom status', $baseline_artifact->getStatus());
        self::assertEquals(5, $baseline_artifact->getInitialEffort());
        self::assertEquals([2, 3, 4], $baseline_artifact->getLinkedArtifactIds());
    }

    public function testFindByIdAt(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(10)->withProject($project)->build();

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact->method('userCanView')->willReturn(true);
        $artifact->method('getTracker')->willReturn($tracker);

        $date = DateTimeFactory::one();

        $this->artifact_factory
            ->method('getArtifactById')
            ->with(1)
            ->willReturn($artifact);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getArtifact')->willReturn($artifact);

        $this->changeset_factory
            ->method('getChangesetAtTimestamp')
            ->with($artifact, $date->getTimestamp())
            ->willReturn($changeset);

        $this->mockSemanticValueAdapter($changeset);

        $this->artifact_link_adapter
            ->method('findLinkedArtifactIds')
            ->with($this->current_tuleap_user, $changeset)
            ->willReturn([2, 3, 4]);

        $baseline_artifact = $this->adapter->findByIdAt($this->current_user, 1, $date);

        self::assertNotNull($baseline_artifact);
        self::assertEquals('Custom title', $baseline_artifact->getTitle());
        self::assertEquals('Custom description', $baseline_artifact->getDescription());
        self::assertEquals('Custom status', $baseline_artifact->getStatus());
        self::assertEquals(5, $baseline_artifact->getInitialEffort());
        self::assertEquals([2, 3, 4], $baseline_artifact->getLinkedArtifactIds());
    }

    private function mockSemanticValueAdapter(Tracker_Artifact_Changeset&MockObject $changeset): void
    {
         $this->semantic_value_adapter->method('findTitle')->with($changeset, $this->current_tuleap_user)->willReturn('Custom title');
         $this->semantic_value_adapter->method('findDescription')->with($changeset, $this->current_tuleap_user)->willReturn('Custom description');
         $this->semantic_value_adapter->method('findStatus')->with($changeset, $this->current_tuleap_user)->willReturn('Custom status');
         $this->semantic_value_adapter->method('findInitialEffort')->with($changeset, $this->current_tuleap_user)->willReturn(5);
    }
}
