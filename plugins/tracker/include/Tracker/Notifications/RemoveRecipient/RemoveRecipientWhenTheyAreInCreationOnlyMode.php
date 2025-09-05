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
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;

final class RemoveRecipientWhenTheyAreInCreationOnlyMode implements RecipientRemovalStrategy
{
    public function __construct(
        private UserNotificationSettingsRetriever $notification_settings_retriever,
    ) {
    }

    /**
     * @psalm-param array<string, Recipient> $recipients
     *
     * @psalm-return array<string, Recipient>
     */
    #[\Override]
    public function removeRecipient(LoggerInterface $logger, Tracker_Artifact_Changeset $changeset, array $recipients, bool $is_update): array
    {
        if (! $is_update) {
            $logger->debug(self::class . ' not an update, skipped');
            return $recipients;
        }

        foreach ($recipients as $key => $recipient) {
            $user_notification_settings = $this->notification_settings_retriever->getUserNotificationSettings(
                $recipient->user,
                $changeset->getTracker()
            );

            if ($user_notification_settings->isInNotifyOnArtifactCreationMode()) {
                $logger->debug(self::class . ' ' . $key . ' removed');
                unset($recipients[$key]);
            }
        }
        return $recipients;
    }
}
