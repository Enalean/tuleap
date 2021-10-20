<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Iteration;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\REST\v1\IterationRepresentation;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;

final class IterationsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private NullLogger $logger;
    private IterationsRetriever $retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    protected function setUp(): void
    {
        $verify_is_program_increment      = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $verify_is_visible_artifact       = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $search_iterations                = SearchIterationsStub::withIterationIds(1);
        $this->tracker_artifact_factory   = $this->createStub(\Tracker_ArtifactFactory::class);
        $this->semantic_timeframe_builder = $this->createStub(SemanticTimeframeBuilder::class);
        $retrieve_user                    = RetrieveUserStub::withGenericUser();
        $this->logger                     = new NullLogger();

        $this->retriever = new IterationsRetriever(
            $verify_is_program_increment,
            $verify_is_visible_artifact,
            $search_iterations,
            $this->tracker_artifact_factory,
            $this->semantic_timeframe_builder,
            $retrieve_user,
            $this->logger,
        );
    }

    public function testItRetrievesIterations(): void
    {
        $id      = 10;
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('getStatusField')->willReturn(null);
        $tracker->method('getName')->willReturn("My_tracker");
        $timeframe_semantic = new SemanticTimeframe($tracker, new TimeframeNotConfigured());
        $this->semantic_timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);
        $iteration_artifact = $this->createStub(Artifact::class);
        $iteration_artifact->method('getId')->willReturn($id);
        $iteration_artifact->method('getTitle')->willReturn("My artifact");
        $iteration_artifact->method('getTracker')->willReturn($tracker);
        $iteration_artifact->method('getUri')->willReturn('trackers?aid=' . $id);
        $iteration_artifact->method('getXRef')->willReturn('story #' . $id);
        $iteration_artifact->method('userCanUpdate')->willReturn(true);
        $user           = UserTestBuilder::buildWithDefaults();
        $representation = IterationRepresentation::buildFromArtifact(
            $this->semantic_timeframe_builder,
            $this->logger,
            $iteration_artifact,
            $user
        );
        $this->tracker_artifact_factory->method('getArtifactById')->willReturn($iteration_artifact);

        $iteration_list = $this->retriever->retrieveIterations($id, UserIdentifierStub::buildGenericUser());
        self::assertCount(1, $iteration_list);
        self::assertEquals($representation, $iteration_list[0]);
    }
}
