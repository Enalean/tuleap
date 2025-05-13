<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login;

use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\Option\Option;
use Tuleap\User\BeforeLogin;
use Tuleap\User\UserAuthenticationSucceeded;

final readonly class LoginEventsGuard
{
    public function __construct(
        private UserMappingManager $mapping_manager,
    ) {
    }

    /**
     * @param Option<\PFUser> $user
     */
    public function verifyLoginEvent(BeforeLogin|UserAuthenticationSucceeded $event, Option $user): void
    {
        $user->apply(
            function (\PFUser $user) use ($event): void {
                if (! $this->mapping_manager->userHasProvider($user)) {
                    return;
                }
                $event->refuseLogin(
                    dgettext('tuleap-openidconnectclient', 'Your account is linked to an OpenID Connect provider, you must use it to authenticate')
                );
            }
        );
    }
}
