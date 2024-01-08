<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\PullRequest\Reviewer\SearchReviewers;

final class SearchReviewersStub implements SearchReviewers
{
    private function __construct(private array $rows)
    {
    }

    public function searchReviewers(int $pull_request_id): array
    {
        return $this->rows;
    }

    public static function fromReviewers(\PFUser $reviewer, \PFUser ...$other_reviewers): self
    {
        $all_rows = [];

        $all_rows[] = self::convertUserToRow($reviewer);

        foreach ($other_reviewers as $other_reviewer) {
            $all_rows[] = self::convertUserToRow($other_reviewer);
        }
        return new self($all_rows);
    }

    private static function convertUserToRow(\PFUser $reviewer): array
    {
        return [
            "user_id" => $reviewer->getId(),
            "user_name" => $reviewer->getUserName(),
            "email" => $reviewer->getEmail(),
            "password" => $reviewer->getPassword(),
            "realname" => $reviewer->getRealName(),
            "register_purpose" => null,
            "status" => $reviewer->getStatus(),
            "ldap_id" => null,
            "add_date" => $reviewer->getAddDate(),
            "approved_by" => 0,
            "confirm_hash" => null,
            "mail_siteupdates" => $reviewer->getMailSiteUpdates(),
            "mail_va" => $reviewer->getMailVA(),
            "sticky_login" => $reviewer->getStickyLogin(),
            "authorized_keys" => null,
            "email_new" => null,
            "timezone" => null,
            "language_id" => $reviewer->getLanguageID(),
            "last_pwd_update" => $reviewer->getLastPwdUpdate(),
            "expiry_date" => null,
            "has_custom_avatar" => $reviewer->hasCustomAvatar(),
            "is_first_timer" => $reviewer->isFirstTimer(),
            "passwordless_only" => 0,
        ];
    }
}
