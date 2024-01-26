<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Notification;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tuleap\NeverThrow\Fault;

final class SendNotificationToTrackerAdministrator implements TrackerAdministratorNotificationSender
{
    public function __construct(
        private readonly TrackerAdminRecipientsRetriever $recipients_retriever,
        private readonly MessageBuilder $message_builder,
        private readonly MessageSender $message_sender,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendNotificationToTrackerAdministrator(\Tracker_Artifact_Changeset $changeset): void
    {
        $this->recipients_retriever->retrieveRecipients($changeset->getTracker())
            ->andThen(fn(array $admins) => $this->message_builder->buildMessagesForAdmins($admins, $changeset))
            ->andThen(fn(array $messages) => $this->message_sender->sendMessages($messages, $changeset))
            ->mapErr(fn(Fault $fault) => Fault::writeToLogger($fault, $this->logger, LogLevel::WARNING));
    }
}
