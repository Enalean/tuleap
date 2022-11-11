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
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushProcessed;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ArtifactClosureNotAllowedFault;
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushReceived;
use Tuleap\NeverThrow\Fault;
use Tuleap\Queue\WorkerEvent;

final class AsynchronousEventHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private DefaultBranchPushParser $push_parser,
        private BuildDefaultBranchPushProcessor $analysis_processor_builder,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function handle(WorkerEvent $event): void
    {
        $this->push_parser->parse($event)
            ->andThen(function (DefaultBranchPushReceived $push) {
                $this->logger->debug(
                    sprintf(
                        'Processing push on default branch of git repository #%d in project #%d by user #%d',
                        (int) $push->getRepository()->getId(),
                        (int) $push->getRepository()->getProject()->getID(),
                        (int) $push->getPusher()->getId(),
                    )
                );
                $processor = $this->analysis_processor_builder->getProcessor($push->getRepository());
                return $processor->process($push);
            })
            ->match(function (DefaultBranchPushProcessed $result) {
                $this->event_dispatcher->dispatch($result->event);
                foreach ($result->faults as $fault) {
                    $this->logger->error((string) $fault);
                }
            }, function (Fault $fault) {
                if ($fault instanceof UnhandledTopicFault || $fault instanceof ArtifactClosureNotAllowedFault) {
                    return;
                }
                $this->logger->error((string) $fault);
            });
    }
}
