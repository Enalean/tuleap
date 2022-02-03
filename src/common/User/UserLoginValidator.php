<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\User;

use Event;
use EventManager;
use Feedback;
use ForgeConfig;
use PFUser;

class UserLoginValidator
{
    public function __construct(private UserNameNormalizer $user_name_normalizer, private EventManager $event_manager)
    {
    }

    public function validateUserLogin(PFUser $user): void
    {
        if (is_numeric($user->getName()) && ForgeConfig::areUnixUsersAvailableOnSystem()) {
            try {
                $new_username = $this->user_name_normalizer->normalize($user->getName());
            } catch (DataIncompatibleWithUsernameGenerationException $exception) {
                return; // nothing done, in order to not block login if something goes wrong
            }

            if ($new_username !== $user->getName()) {
                $this->event_manager->processEvent(Event::USER_RENAME, [
                    'user_id'  => $user->getId(),
                    'new_name' => $new_username,
                    'old_user' => $user,
                ]);

                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        dgettext(
                            'tuleap-core',
                            'Your old Tuleap login was not valid against the configuration of your platform. It will be changed to "%s". If you use Ldap, it will not change your ldap login.',
                        ),
                        $new_username
                    )
                );
            }
        }
    }
}
