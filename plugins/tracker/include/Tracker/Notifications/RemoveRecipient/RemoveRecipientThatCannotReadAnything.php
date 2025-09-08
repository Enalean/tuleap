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

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Notifications\RecipientRemovalStrategy;

final class RemoveRecipientThatCannotReadAnything implements RecipientRemovalStrategy
{
    public function __construct()
    {
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
        foreach ($recipients as $key => $recipient) {
            if (! $recipient->check_permissions) {
                continue;
            }

            if (! $changeset->getArtifact()->userCanView($recipient->user)) {
                $logger->debug(self::class . ' ' . $key . ' removed, artifact cannot be seen by the user');
                unset($recipients[$key]);
                continue;
            }

            if ($this->isThereANonEmptyChangesetComment($changeset)) {
                continue;
            }

            if (! $this->userCanReadAtLeastOneChangedField($changeset, $recipient->user)) {
                $logger->debug(self::class . ' ' . $key . ' removed, no visible changes for the user');
                unset($recipients[$key]);
            }
        }

        return $recipients;
    }

    private function userCanReadAtLeastOneChangedField(Tracker_Artifact_Changeset $changeset, PFUser $user): bool
    {
        foreach ($changeset->getValues() as $current_changeset_value) {
            $field_has_changed = $current_changeset_value && $current_changeset_value->hasChanged();
            if (! $field_has_changed) {
                continue;
            }
            $field             = $current_changeset_value->getField();
            $field_is_readable = $field && $field->userCanRead($user);
            if ($field_is_readable) {
                return true;
            }
        }
        return false;
    }

    private function isThereANonEmptyChangesetComment(Tracker_Artifact_Changeset $changeset): bool
    {
        $comment = $changeset->getComment();

        return $comment !== null && ! $comment->hasEmptyBody();
    }
}
