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
use Tuleap\Git\CommitMetadata\RetrieveCommitMessage;

final class CommitAnalysisProcessor
{
    public function __construct(
        private LoggerInterface $logger,
        private RetrieveCommitMessage $message_retriever,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function process(CommitAnalysisOrder $order): void
    {
        try {
            $commit_message = $this->message_retriever->getCommitMessage((string) $order->getCommitHash());
        } catch (\Git_Command_Exception $e) {
            $this->logger->error('Could not retrieve commit message', ['exception' => $e]);
            return;
        }
        $this->event_dispatcher->dispatch(new PotentialReferencesReceived($commit_message, $order->getProject()));
    }
}
