<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Adapter\JSON\PendingIterationUpdateRepresentation;
use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\DispatchIterationUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\Worker;

final readonly class IterationUpdateDispatcher implements DispatchIterationUpdate
{
    public function __construct(
        private QueueFactory $queue_factory,
    ) {
    }

    #[\Override]
    public function dispatchUpdate(IterationUpdate $update): void
    {
        $representation = PendingIterationUpdateRepresentation::fromIterationUpdate($update);
        $this->processUpdateAsynchronously($representation);
    }

    private function processUpdateAsynchronously(PendingIterationUpdateRepresentation $representation): void
    {
        $queue = $this->queue_factory->getPersistentQueue(Worker::EVENT_QUEUE_NAME);
        $queue->pushSinglePersistentMessage(IterationUpdateEvent::TOPIC, $representation);
    }
}
