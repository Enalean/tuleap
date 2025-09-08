<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Notifications\RemoveRecipient;

use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Notifications\RecipientRemovalStrategy;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;

final class RemoveRecipientThatHaveUnsubscribedFromNotification implements RecipientRemovalStrategy
{
    public function __construct(
        private UnsubscribersNotificationDAO $unsubscribers_notification_dao,
    ) {
    }

    /**
     * @psalm-param array<string, Recipient> $recipients
     *
     * @psalm-return array<string, Recipient>
     */
    #[\Override]
    public function removeRecipient(
        LoggerInterface $logger,
        Tracker_Artifact_Changeset $changeset,
        array $recipients,
        bool $is_update,
    ): array {
        $tracker       = $changeset->getTracker();
        $artifact      = $changeset->getArtifact();
        $unsubscribers = $this->unsubscribers_notification_dao->searchUserIDHavingUnsubcribedFromNotificationByTrackerOrArtifactID(
            $tracker->getId(),
            $artifact->getId()
        );

        foreach ($recipients as $key => $recipient) {
            if (in_array($recipient->user->getId(), $unsubscribers)) {
                $logger->debug(self::class . ' ' . $key . ' removed');
                unset($recipients[$key]);
            }
        }

        return $recipients;
    }
}
