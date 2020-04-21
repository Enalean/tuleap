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

namespace Tuleap\Tracker\Workflow\SimpleMode\State;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Transition;
use TransitionFactory;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;
use Workflow;

class TransitionCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $transition_factory;
    private $transition_replicator;
    private $transition_extractor;

    /**
     * @var TransitionCreator
     */
    private $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transition_factory    = Mockery::mock(TransitionFactory::class);
        $this->transition_replicator = Mockery::mock(TransitionReplicator::class);
        $this->transition_extractor  = Mockery::mock(TransitionExtractor::class);

        $this->creator = new TransitionCreator(
            $this->transition_factory,
            $this->transition_replicator,
            $this->transition_extractor
        );
    }

    public function testCreatesTransitionInWorkfowState()
    {
        $state    = Mockery::mock(State::class);
        $workflow = Mockery::mock(Workflow::class);
        $params   = new TransitionCreationParameters(100, 101);

        $transition           = Mockery::mock(Transition::class);
        $reference_transition = Mockery::mock(Transition::class);

        $this->transition_factory->shouldReceive('createAndSaveTransition')
            ->with($workflow, $params)
            ->once()
            ->andReturn($transition);

        $this->transition_extractor->shouldReceive('extractReferenceTransitionFromState')
            ->with($state)
            ->once()
            ->andReturn($reference_transition);

        $this->transition_replicator->shouldReceive('replicate')
            ->with($reference_transition, $transition)
            ->once();

        $this->creator->createTransitionInState(
            $state,
            $workflow,
            $params
        );
    }

    public function testCreatesFirstTransitionInState()
    {
        $state    = Mockery::mock(State::class);
        $workflow = Mockery::mock(Workflow::class);
        $params   = new TransitionCreationParameters(100, 101);

        $transition = Mockery::mock(Transition::class);

        $this->transition_factory->shouldReceive('createAndSaveTransition')
            ->with($workflow, $params)
            ->once()
            ->andReturn($transition);

        $this->transition_extractor->shouldReceive('extractReferenceTransitionFromState')
            ->with($state)
            ->once()
            ->andThrow(NoTransitionForStateException::class);

        $this->transition_replicator->shouldReceive('replicate')->never();

        $this->creator->createTransitionInState(
            $state,
            $workflow,
            $params
        );
    }
}
