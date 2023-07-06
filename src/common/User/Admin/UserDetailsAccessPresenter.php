<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\User\Admin;

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\InviteBuddy\Admin\InvitedByPresenter;

final class UserDetailsAccessPresenter
{
    public readonly ?InvitedByPresenter $invited_by;
    public readonly string $last_access_date_label;
    public readonly ?TlpRelativeDatePresenter $last_access_date;
    public readonly string $last_pwd_update_label;
    public readonly ?TlpRelativeDatePresenter $last_password_update_date;
    public readonly ?TlpRelativeDatePresenter $auth_attempt_last_success;
    public readonly ?TlpRelativeDatePresenter $auth_attempt_last_failure;
    public readonly mixed $auth_attempt_nb_failure;
    public readonly string $member_since_label;
    public readonly TlpRelativeDatePresenter $member_since;

    public function __construct(
        \PFUser $current_user,
        \PFUser $displayed_user,
        array $user_info,
        ?InvitedByPresenter $invited_by,
    ) {
        $relative_date_builder        = new TlpRelativeDatePresenterBuilder();
        $this->last_access_date_label = $GLOBALS['Language']->getText('admin_usergroup', 'last_access_date');
        $this->last_access_date       = $this->getOptionalDatePresenter(
            (int) $user_info['last_access_date'],
            $relative_date_builder,
            $current_user
        );

        $this->last_pwd_update_label     = $GLOBALS['Language']->getText('admin_usergroup', 'last_pwd_update');
        $this->last_password_update_date = $this->getOptionalDatePresenter(
            (int) $displayed_user->getLastPwdUpdate(),
            $relative_date_builder,
            $current_user
        );

        $this->auth_attempt_last_success = $this->getOptionalDatePresenter(
            (int) $user_info['last_auth_success'],
            $relative_date_builder,
            $current_user
        );

        $this->auth_attempt_last_failure = $this->getOptionalDatePresenter(
            (int) $user_info['last_auth_failure'],
            $relative_date_builder,
            $current_user
        );

        $this->auth_attempt_nb_failure = $user_info['nb_auth_failure'];

        $this->member_since_label = $GLOBALS['Language']->getText('include_user_home', 'member_since');
        $this->member_since       = $relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
            new \DateTimeImmutable('@' . $displayed_user->getAddDate()),
            $current_user
        );
        $this->invited_by         = $invited_by;
    }

    private function getOptionalDatePresenter(
        int $timestamp,
        TlpRelativeDatePresenterBuilder $relative_date_builder,
        \PFUser $current_user,
    ): ?TlpRelativeDatePresenter {
        if ($timestamp === 0) {
            return null;
        }
        return $relative_date_builder->getTlpRelativeDatePresenterInInlineContext(
            new \DateTimeImmutable('@' . $timestamp),
            $current_user
        );
    }
}
