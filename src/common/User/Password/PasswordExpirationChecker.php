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

declare(strict_types=1);

namespace Tuleap\User\Password;

use Feedback;
use ForgeConfig;
use PFUser;
use Tuleap\Date\DateHelper;

class PasswordExpirationChecker
{
    public const int DAYS_FOR_EXPIRATION_WARN = 10;

    /**
     *
     * @throws PasswordExpiredException
     */
    public function checkPasswordLifetime(PFUser $user): void
    {
        if ($this->userPasswordHasExpired($user)) {
            throw new PasswordExpiredException($user);
        }
    }

    public function warnUserAboutPasswordExpiration(PFUser $user): void
    {
        $password_lifetime_in_seconds = $this->getPasswordLifetimeInSeconds();
        if ($password_lifetime_in_seconds !== false && $password_lifetime_in_seconds > 0) {
            $expiration_date = $this->getPasswordExpirationDate();
            if ($expiration_date === false || $expiration_date <= 0) {
                return;
            }
            $warning_date = $expiration_date + DateHelper::SECONDS_IN_A_DAY * self::DAYS_FOR_EXPIRATION_WARN;
            if ($user->getLastPwdUpdate() < $warning_date) {
                $expiration_delay = (int) ceil(($user->getLastPwdUpdate() - $expiration_date) / ( DateHelper::SECONDS_IN_A_DAY ));
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        ngettext('Your password will expire in %d day.', 'Your password will expire in %d days.', $expiration_delay),
                        $expiration_delay
                    )
                );
            }
        }
    }

    private function userPasswordHasExpired(PFUser $user): bool
    {
        $expiration_date = $this->getPasswordExpirationDate();
        if ($expiration_date !== false && $expiration_date > 0 && $user->getLastPwdUpdate() < $expiration_date) {
            return true;
        }
        return false;
    }

    private function getPasswordExpirationDate(): int|false
    {
        $password_lifetime = $this->getPasswordLifetimeInSeconds();
        if ($password_lifetime !== false && $password_lifetime > 0) {
            return $_SERVER['REQUEST_TIME'] - $password_lifetime;
        }
        return false;
    }

    private function getPasswordLifetimeInSeconds(): int|false
    {
        $password_lifetime_in_days = ForgeConfig::getInt('sys_password_lifetime');
        if ($password_lifetime_in_days) {
            return DateHelper::SECONDS_IN_A_DAY * $password_lifetime_in_days;
        }
        return false;
    }
}
