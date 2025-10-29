<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\SeatManagement;

use DateTimeImmutable;
use Feedback;
use PFUser;
use Psr\Clock\ClockInterface;

final readonly class FeedbackBuilder
{
    public function __construct(private BuildLicense $license_builder, private PFUser $current_user, private ClockInterface $clock)
    {
    }

    public function build(Feedback $feedback): void
    {
        $license = $this->license_builder->build();

        if ($this->current_user->isSuperUser()) {
            $this->buildForSuperUser($feedback, $license);
        } elseif (! $this->current_user->isAnonymous()) {
            $this->buildForRegularUser($feedback, $license);
        }
    }

    private function buildForSuperUser(Feedback $feedback, License $license): void
    {
        $license->expiration_date->apply(function (DateTimeImmutable $expiration_date) use ($feedback, $license) {
            if ($this->isOneMonthBeforeExpiration($expiration_date)) {
                $nb_days_before_expiration = $this->clock->now()->diff($expiration_date)->days;

                $feedback->log(Feedback::WARN, sprintf(
                    ngettext(
                        'Your Tuleap subscription will expire in %d day. Please renew to avoid any disruption. %s',
                        'Your Tuleap subscription will expire in %d days. Please renew to avoid any disruption. %s',
                        $nb_days_before_expiration,
                    ),
                    $nb_days_before_expiration,
                    $this->getInfoContact($license),
                ));
            }
            if ($this->isAtExpirationDate($expiration_date)) {
                $nb_days_before_expiration_plus_one_month = $expiration_date->modify('+1 month')->diff($this->clock->now())->days;

                $feedback->log(Feedback::WARN, sprintf(
                    ngettext(
                        'Your Tuleap subscription has expired. All accounts will be in read only mode in %d day. %s',
                        'Your Tuleap subscription has expired. All accounts will be in read only mode in %d days. %s',
                        $nb_days_before_expiration_plus_one_month,
                    ),
                    $nb_days_before_expiration_plus_one_month,
                    $this->getInfoContact($license),
                ));
            }
        });
    }

    private function getInfoContact(License $license): string
    {
        return match ($license->license_kind) {
            LicenseKind::EXPERT, LicenseKind::TCP => _('Please get in touch with your usual company contact or send an email to sales@enalean.com.'),
            LicenseKind::MY_TULEAP                => _('Please renew or upgrade your contract on your client account or send an email to sales@enalean.com.'),
            LicenseKind::PARTNER                  => _('Please contact your Tuleap Partner to renew or upgrade your subscription.'),
        };
    }

    private function buildForRegularUser(Feedback $feedback, License $license): void
    {
        $license->expiration_date->apply(function (DateTimeImmutable $expiration_date) use ($feedback) {
            if ($this->isAtExpirationDate($expiration_date)) {
                $feedback->log(Feedback::WARN, _('Your subscription has expired. Please contact your administrator to continue using Tuleap.'));
            }
        });
    }

    private function isOneMonthBeforeExpiration(DateTimeImmutable $expiration_date): bool
    {
        $today                       = $this->clock->now();
        $one_month_before_expiration = $expiration_date->modify('-1 month');

        return $today >= $one_month_before_expiration && $today < $expiration_date;
    }

    private function isAtExpirationDate(DateTimeImmutable $expiration_date): bool
    {
        $today                      = $this->clock->now();
        $one_month_after_expiration = $expiration_date->modify('+1 month');

        return $today >= $expiration_date && $today < $one_month_after_expiration;
    }
}
