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

namespace Tuleap\InviteBuddy;

enum InvitationHistoryEntry: string
{
    case InvitationSent      = 'invitation_sent';
    case InvitationWithdrawn = 'invitation_withdrawn';
    case InvitationResent    = 'invitation_resent';
    case InvitationCompleted = 'invitation_completed';

    public function getLabel(): string
    {
        return match ($this) {
            self::InvitationSent      => _('Sent invitation'),
            self::InvitationWithdrawn => _('Withdrawn invitation'),
            self::InvitationResent    => _('Resent invitation'),
            self::InvitationCompleted => _('Completed invitation'),
        };
    }
}
