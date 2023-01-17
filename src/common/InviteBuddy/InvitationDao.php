<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DataAccessObject;

class InvitationDao extends DataAccessObject implements InvitationByTokenRetriever
{
    public function __construct(private SplitTokenVerificationStringHasher $hasher)
    {
        parent::__construct();
    }

    public function create(
        int $created_on,
        int $from_user_id,
        string $to_email,
        ?int $to_user_id,
        ?string $custom_message,
        string $status,
        SplitTokenVerificationString $verifier,
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'invitations',
            [
                'created_on'     => $created_on,
                'from_user_id'   => $from_user_id,
                'to_email'       => $to_email,
                'to_user_id'     => $to_user_id,
                'custom_message' => $custom_message,
                'status'         => $status,
                'verifier'       => $this->hasher->computeHash($verifier),
            ]
        );
    }

    public function update(int $id, string $status): void
    {
        $this->getDB()->update('invitations', ['status' => $status], ['id' => $id]);
    }

    /**
     * @throws InvalidInvitationTokenException
     */
    public function searchBySplitToken(SplitToken $split_token): Invitation
    {
        $row = $this->getDB()->row('SELECT to_email, verifier FROM invitations WHERE id = ?', $split_token->getID());
        if (! $this->hasher->verifyHash($split_token->getVerificationString(), $row['verifier'])) {
            throw new InvalidInvitationTokenException();
        }

        return new Invitation($row['to_email']);
    }

    public function searchByEmail(string $to_email): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT from_user_id FROM invitations WHERE to_email = ? AND status = ?",
            $to_email,
            InvitationSender::STATUS_SENT,
        );
    }

    public function saveJustCreatedUserThanksToInvitation(string $to_email, int $just_created_user_id): void
    {
        $this->getDB()->run(
            "UPDATE invitations
                SET created_user_id = ?
                WHERE to_email = ?
                  AND status = ?
                  AND created_user_id IS NULL",
            $just_created_user_id,
            $to_email,
            InvitationSender::STATUS_SENT,
        );
    }

    public function searchUserIdThatInvitedUser(int $user_id): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT from_user_id FROM invitations WHERE created_user_id = ?",
            $user_id,
        );
    }

    public function getInvitationsSentByUserForToday(int $user_id): int
    {
        $sql = "SELECT count(*)
                FROM invitations
                WHERE from_user_id = ? AND DATE(FROM_UNIXTIME(created_on)) = CURDATE()";

        return (int) $this->getDB()->single($sql, [$user_id]);
    }
}
