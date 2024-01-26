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

use Tracker_Artifact_Changeset;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\SendMail;

final class SendMessagesForAdmins implements MessageSender
{
    public function __construct(
        private readonly SendMail $mail_sender,
    ) {
    }

    public function sendMessages(array $messages, Tracker_Artifact_Changeset $changeset): Ok | Err
    {
        foreach ($messages as $message) {
            $this->mail_sender->send(
                $changeset,
                $message->recipients,
                $message->headers,
                $message->from,
                $message->subject,
                $message->htmlBody,
                $message->txtBody,
                null,
                $message->attachments,
            );
        }

        return Result::ok(null);
    }
}
