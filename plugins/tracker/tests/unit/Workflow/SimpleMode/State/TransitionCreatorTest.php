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

use PHPUnit\Framework\MockObject\MockObject;
use Transition;
use TransitionFactory;
use Tuleap\Tracker\Workflow\SimpleMode\TransitionReplicator;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;
use Tuleap\Tracker\Workflow\Transition\TransitionCreationParameters;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TransitionFactory&MockObject $transition_factory;
    private TransitionReplicator&MockObject $transition_replicator;
    private TransitionExtractor&MockObject $transition_extractor;

    private TransitionCreator $creator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->transition_factory    = $this->createMock(TransitionFactory::class);
        $this->transition_replicator = $this->createMock(TransitionReplicator::class);
        $this->transition_extractor  = $this->createMock(TransitionExtractor::class);

        $this->creator = new TransitionCreator(
            $this->transition_factory,
            $this->transition_replicator,
            $this->transition_extractor
        );
    }

    public function testCreatesTransitionInWorkfowState()
    {
        $state    = $this->createMock(State::class);
        $workflow = $this->createMock(Workflow::class);
        $params   = new TransitionCreationParameters(100, 101);

        $transition           = $this->createMock(Transition::class);
        $reference_transition = $this->createMock(Transition::class);

        $this->transition_factory
            ->expects($this->once())
            ->method('createAndSaveTransition')
            ->with($workflow, $params)
            ->willReturn($transition);

        $this->transition_extractor
            ->expects($this->once())
            ->method('extractReferenceTransitionFromState')
            ->with($state)
            ->willReturn($reference_transition);

        $this->transition_replicator
            ->expects($this->once())
            ->method('replicate')
            ->with($reference_transition, $transition);

        $this->creator->createTransitionInState(
            $state,
            $workflow,
            $params
        );
    }

    public function testCreatesFirstTransitionInState()
    {
        $state    = $this->createMock(State::class);
        $workflow = $this->createMock(Workflow::class);
        $params   = new TransitionCreationParameters(100, 101);

        $transition = $this->createMock(Transition::class);

        $this->transition_factory
            ->expects($this->once())
            ->method('createAndSaveTransition')
            ->with($workflow, $params)
            ->willReturn($transition);

        $this->transition_extractor
            ->expects($this->once())
            ->method('extractReferenceTransitionFromState')
            ->with($state)
            ->willThrowException(new NoTransitionForStateException());

        $this->transition_replicator->expects($this->never())->method('replicate');

        $this->creator->createTransitionInState(
            $state,
            $workflow,
            $params
        );
    }
}
