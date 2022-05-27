<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Config\ConfigKey;
use Tuleap\User\Account\RegistrationGuardEvent;

class InviteBuddyConfiguration
{
    #[ConfigKey("How many invitations a user can send per day? (default 20)")]
    public const CONFIG_MAX_INVITATIONS_BY_DAY = 'max_invitations_by_day';

    private const CONFIG_MAX_INVITATIONS_BY_DAY_DEFAULT = 20;

    public function __construct(private EventDispatcherInterface $event_dispatcher)
    {
    }

    public function canBuddiesBeInvited(\PFUser $current_user): bool
    {
        return ! $current_user->isAnonymous()
            && $this->isFeatureEnabled();
    }

    public function isFeatureEnabled(): bool
    {
        return $this->isRegistrationPossible()
            && $this->getNbMaxInvitationsByDay() > 0;
    }

    public function canSiteAdminConfigureTheFeature(): bool
    {
        return $this->isRegistrationPossible();
    }

    public function getNbMaxInvitationsByDay(): int
    {
        return \ForgeConfig::getInt(
            self::CONFIG_MAX_INVITATIONS_BY_DAY,
            self::CONFIG_MAX_INVITATIONS_BY_DAY_DEFAULT
        );
    }

    private function isRegistrationPossible(): bool
    {
        $registration_guard = $this->event_dispatcher->dispatch(new RegistrationGuardEvent());

        return $registration_guard->isRegistrationPossible();
    }
}
