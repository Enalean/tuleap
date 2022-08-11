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
use Tuleap\Git\Repository\Settings\ArtifactClosure\ArtifactClosureNotAllowedFault;
use Tuleap\Git\Hook\DefaultBranchPush\DefaultBranchPushReceived;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
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
            ->match(function (DefaultBranchPushReceived $push) {
                $processor = $this->analysis_processor_builder->getProcessor($push->getRepository());
                // We intentionally do not combine the list of results.
                // We want to continue processing other commits in case of fault.
                foreach ($processor->process($push) as $result) {
                    $this->handleSingleResult($result);
                }
            }, function (Fault $fault) {
                if ($fault instanceof UnhandledTopicFault) {
                    return;
                }
                $this->logger->error((string) $fault);
            });
    }

    /**
     * @param Ok<PotentialReferencesReceived> | Err<Fault> $result
     */
    private function handleSingleResult(Ok|Err $result): void
    {
        $result->match(function (PotentialReferencesReceived $event) {
            $this->logger->debug(
                sprintf(
                    'Searching for references in commit message of %s in project #%d by user #%d',
                    $event->back_reference->getStringReference(),
                    (int) $event->project->getID(),
                    (int) $event->user->getId(),
                )
            );
            $this->event_dispatcher->dispatch($event);
        }, function (Fault $fault) {
            if ($fault instanceof ArtifactClosureNotAllowedFault) {
                return;
            }
            $this->logger->error((string) $fault);
        });
    }
}
