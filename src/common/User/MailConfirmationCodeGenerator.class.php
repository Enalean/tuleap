<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class MailConfirmationCodeGenerator
{

    /** @var UserManager */
    private $user_manager;
    /** @var RandomNumberGenerator */
    private $random_generator;

    public function __construct(UserManager $user_manager, RandomNumberGenerator $random_generator)
    {
        $this->user_manager     = $user_manager;
        $this->random_generator = $random_generator;
    }

    /**
     * @return string
     */
    public function getConfirmationCode()
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
