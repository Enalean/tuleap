<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tracker_Semantic_Title;
use Tuleap\Mail\MailAttachment;
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;

final class EmailNotificationAttachmentProvider implements ProvideEmailNotificationAttachment
{
    public function __construct(private readonly CheckEventShouldBeSentInNotification $config)
    {
    }

    /**
     * @return MailAttachment[]
     */
    public function getAttachments(
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        \Psr\Log\LoggerInterface $logger,
        bool $should_check_permissions,
    ): array {
        if ($this->config->shouldSendEventInNotification($changeset->getTracker()->getId())) {
            $logger->debug('Tracker is configured to send calendar events alongside notification');
            $title_field = Tracker_Semantic_Title::load($changeset->getTracker())->getField();
            if (! $title_field) {
                $logger->debug('The tracker does not have title semantic, we cannot build calendar events');
                return [];
            }
            if ($should_check_permissions && ! $title_field->userCanRead($recipient)) {
                $logger->debug(
                    sprintf(
                        'The user #%s (%s) cannot read the title, we cannot build calendar events',
                        $recipient->getId(),
                        $recipient->getEmail(),
                    )
                );
                return [];
            }

            $logger->debug('No calendar event for this changeset');
        }

        return [];
    }
}
