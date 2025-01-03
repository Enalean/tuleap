<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Date\DateHelper;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class User_PasswordExpirationChecker
{
    public const DAYS_FOR_EXPIRATION_WARN = 10;

    /**
     *
     * @throws User_PasswordExpiredException
     */
    public function checkPasswordLifetime(PFUser $user)
    {
        if ($this->userPasswordHasExpired($user)) {
            throw new User_PasswordExpiredException($user);
        }
    }

    public function warnUserAboutPasswordExpiration(PFUser $user)
    {
        if ($this->getPasswordLifetimeInSeconds()) {
            $expiration_date = $this->getPasswordExpirationDate();
            $warning_date    = $expiration_date + DateHelper::SECONDS_IN_A_DAY * self::DAYS_FOR_EXPIRATION_WARN;
            if ($user->getLastPwdUpdate() < $warning_date) {
                $expiration_delay = ceil(($user->getLastPwdUpdate() - $expiration_date) / ( DateHelper::SECONDS_IN_A_DAY ));
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    $GLOBALS['Language']->getText('include_session', 'password_will_expire', $expiration_delay)
                );
            }
        }
    }

    private function userPasswordHasExpired(PFUser $user)
    {
        $expiration_date = $this->getPasswordExpirationDate();
        if ($expiration_date && $user->getLastPwdUpdate() < $expiration_date) {
            return true;
        }
        return false;
    }

    private function getPasswordExpirationDate()
    {
        $password_lifetime = $this->getPasswordLifetimeInSeconds();
        if ($password_lifetime) {
            return $_SERVER['REQUEST_TIME'] - $password_lifetime;
        }
        return false;
    }

    private function getPasswordLifetimeInSeconds()
    {
        $password_lifetime_in_days = ForgeConfig::get('sys_password_lifetime');
        if ($password_lifetime_in_days) {
            return DateHelper::SECONDS_IN_A_DAY * $password_lifetime_in_days;
        }
        return false;
    }
}
