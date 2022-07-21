<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Git\Hook\Asynchronous;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Event\Events\PotentialReferencesReceived;
use Tuleap\NeverThrow\Fault;
use Tuleap\Queue\WorkerEvent;

final class AsynchronousEventHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private CommitAnalysisOrderParser $order_parser,
        private BuildCommitAnalysisProcessor $analysis_processor_builder,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function handle(WorkerEvent $event): void
    {
        $this->order_parser->parse($event)
            ->andThen(function (CommitAnalysisOrder $order) {
                $processor = $this->analysis_processor_builder->getProcessor($order->getRepository());
                return $processor->process($order);
            })
            ->match(
                function (PotentialReferencesReceived $event) {
                    $this->logger->debug(
                        sprintf(
                            'Searching for references in commit message of %s in project #%d by user #%d',
                            $event->back_reference->getStringReference(),
                            (int) $event->project->getID(),
                            (int) $event->user->getId(),
                        )
                    );
                    $this->event_dispatcher->dispatch($event);
                },
                function (Fault $fault) {
                    if ($fault instanceof UnhandledTopicFault) {
                        return;
                    }
                    $this->logger->error((string) $fault);
                }
            );
    }
}
