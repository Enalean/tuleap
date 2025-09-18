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

use DateTimeImmutable;
use Feedback;
use ForgeConfig;
use PFUser;
use Psr\Clock\ClockInterface;
use Tuleap\Config\ConfigDateValueValidator;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\ConfigKeyString;
use Tuleap\Config\ConfigKeyValueValidator;
use Tuleap\Layout\BaseLayout;
use Tuleap\Option\Option;

readonly class PasswordExpirationChecker
{
    #[ConfigKey('Default password duration')]
    #[ConfigKeyHelp('User will be asked to change its password after sys_password_lifetime days; 0 = no duration')]
    #[ConfigKeyInt(0)]
    public const string PASSWORD_LIFETIME = 'sys_password_lifetime';

    #[ConfigKey('Password expiration date')]
    #[ConfigKeyHelp('Password not changed after this date are considered expired; no restriction by default')]
    #[ConfigKeyString('')]
    #[ConfigKeyValueValidator(ConfigDateValueValidator::class)]
    public const string PASSWORD_EXPIRATION_DATE = 'sys_password_expiration_date';

    private const int DAYS_FOR_EXPIRATION_WARN = 10;

    public function __construct(private ClockInterface $clock)
    {
    }

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

    public function warnUserAboutPasswordExpiration(BaseLayout $layout, PFUser $user): void
    {
        $this->getPasswordExpirationDate($user)
            ->apply(
                function (DateTimeImmutable $expiration_date) use ($layout, $user): void {
                    $remaining_time = (int) $this->clock->now()->diff($expiration_date)->days;
                    if ($remaining_time <= self::DAYS_FOR_EXPIRATION_WARN) {
                        $layout->addFeedback(
                            Feedback::WARN,
                            sprintf(
                                ngettext('Your password will expire in %d day.', 'Your password will expire in %d days.', $remaining_time),
                                $remaining_time
                            )
                        );
                    }
                }
            );
    }

    private function userPasswordHasExpired(PFUser $user): bool
    {
        return $this->getPasswordExpirationDate($user)
            ->mapOr(
                fn (DateTimeImmutable $expiration_date): bool => $this->clock->now() > $expiration_date,
                false
            );
    }

    /**
     * @return Option<DateTimeImmutable>
     */
    private function getPasswordExpirationDate(PFUser $user): Option
    {
        return $this->getPasswordLifetimeExpirationDate($user)
            ->mapOr(
                /**
                 * @param DateTimeImmutable $lifetime_expiration_date
                 * @return Option<DateTimeImmutable>
                 */
                function (DateTimeImmutable $lifetime_expiration_date): Option {
                    return $this->getPasswordHardExpirationDate()
                        ->mapOr(
                        /**
                         * @return Option<DateTimeImmutable>
                         */
                            fn (DateTimeImmutable $hard_expiration_date): Option => Option::fromValue(min($lifetime_expiration_date, $hard_expiration_date)),
                            Option::fromValue($lifetime_expiration_date)
                        );
                },
                $this->getPasswordHardExpirationDate()
            );
    }

    /**
     * @return Option<DateTimeImmutable>
     */
    private function getPasswordLifetimeExpirationDate(PFUser $user): Option
    {
        $password_lifetime_in_days = ForgeConfig::getInt(self::PASSWORD_LIFETIME);
        if ($password_lifetime_in_days > 0) {
            $last_password_update_date = DateTimeImmutable::createFromTimestamp($user->getLastPwdUpdate());
            $password_expiration_date  = $last_password_update_date->add(new \DateInterval('P' . $password_lifetime_in_days . 'D'));
            assert($password_expiration_date instanceof DateTimeImmutable);
            return Option::fromValue($password_expiration_date);
        }

        return Option::nothing(DateTimeImmutable::class);
    }

    /**
     * @return Option<DateTimeImmutable>
     */
    private function getPasswordHardExpirationDate(): Option
    {
        $hard_expiration_date = DateTimeImmutable::createFromFormat(
            DateTimeImmutable::ATOM,
            ForgeConfig::get(self::PASSWORD_EXPIRATION_DATE, '')
        );
        if ($hard_expiration_date !== false) {
            return Option::fromValue($hard_expiration_date);
        }

        return Option::nothing(DateTimeImmutable::class);
    }
}
