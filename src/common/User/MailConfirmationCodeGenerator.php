<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\User;

final class MailConfirmationCodeGenerator implements IGenerateMailConfirmationCode
{
    public function __construct(private \UserManager $user_manager, private \RandomNumberGenerator $random_generator)
    {
    }

    #[\Override]
    public function getConfirmationCode(): string
    {
        $confirmation_code = null;

        while ($confirmation_code === null) {
            $possible_confirmation_code = $this->random_generator->getNumber();
            if ($this->user_manager->getUserByConfirmHash($possible_confirmation_code) === null) {
                $confirmation_code = $possible_confirmation_code;
            }
        }

        return $confirmation_code;
    }
}
