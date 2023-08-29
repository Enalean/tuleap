<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\User\Account\Register;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\InviteBuddy\InvitationSuccessFeedback;
use Tuleap\User\ICreateAccount;

final class AccountRegister
{
    private const DEFAULT_LDAP_ID = '';

    public function __construct(
        private ICreateAccount $account_creator,
        private InvitationSuccessFeedback $invitation_success_feedback,
    ) {
    }

    public function register(
        string $loginname,
        ?ConcealedString $password,
        string $realname,
        string $register_purpose,
        string $email,
        string $status,
        ?string $confirm_hash,
        string $mail_site,
        string $mail_va,
        string $timezone,
        string $lang_id,
        int|string $expiry_date,
        RegisterFormContext $context,
    ): ?\PFUser {
        $user = new \PFUser();
        $user->setUserName($loginname);
        $user->setRealName($realname);
        if ($password !== null) {
            $user->setPassword($password);
        }
        $user->setLdapId(self::DEFAULT_LDAP_ID);
        $user->setRegisterPurpose($register_purpose);
        $user->setEmail($email);
        $user->setStatus($status);
        if ($confirm_hash) {
            $user->setConfirmHash($confirm_hash);
        }
        $user->setMailSiteUpdates($mail_site);
        $user->setMailVA($mail_va);
        $user->setTimezone($timezone);
        $user->setLanguageID($lang_id);
        $user->setExpiryDate($expiry_date);

        $created_user = $this->account_creator->createAccount($user);
        if ($created_user) {
            $this->invitation_success_feedback->accountHasJustBeenCreated($created_user, $context);
        }

        return $created_user;
    }
}
