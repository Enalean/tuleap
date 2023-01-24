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

class InvitationDao extends DataAccessObject implements InvitationByTokenRetriever, UsedInvitationRetriever
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
                'status'         => Invitation::STATUS_CREATING,
                'verifier'       => $this->hasher->computeHash($verifier),
            ]
        );
    }

    public function markAsSent(int $id): void
    {
        $this->getDB()->update('invitations', ['status' => Invitation::STATUS_SENT], ['id' => $id]);
    }

    public function markAsError(int $id): void
    {
        $this->getDB()->update(
            'invitations',
            [
                'status'   => Invitation::STATUS_ERROR,
                'to_email' => '',
            ],
            ['id' => $id]
        );
    }

    /**
     * @throws InvalidInvitationTokenException|InvitationNotFoundException
     */
    public function searchBySplitToken(SplitToken $split_token): Invitation
    {
        $row = $this->getDB()->row('SELECT id, to_email, to_user_id, from_user_id, created_user_id, verifier FROM invitations WHERE id = ?', $split_token->getID());
        if (! $row) {
            throw new InvitationNotFoundException();
        }

        if (! $this->hasher->verifyHash($split_token->getVerificationString(), $row['verifier'])) {
            throw new InvalidInvitationTokenException();
        }

        return new Invitation($row['id'], $row['to_email'], $row['to_user_id'], $row['from_user_id'], $row['created_user_id']);
    }

    public function searchByCreatedUserId(int $user_id): array
    {
        return $this->getDB()->run(
            "SELECT DISTINCT from_user_id FROM invitations WHERE created_user_id = ? AND status IN (?, ?)",
            $user_id,
            Invitation::STATUS_SENT,
            Invitation::STATUS_USED,
        );
    }

    public function saveJustCreatedUserThanksToInvitation(string $to_email, int $just_created_user_id, ?int $used_invitation_id): void
    {
        $this->getDB()->run(
            "UPDATE invitations
                SET created_user_id = ?,
                    status = IF(id = ?, ?, status),
                    to_email = ''
                WHERE to_email = ?
                  AND status = ?
                  AND created_user_id IS NULL",
            $just_created_user_id,
            $used_invitation_id,
            Invitation::STATUS_USED,
            $to_email,
            Invitation::STATUS_SENT,
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

    public function hasUsedAnInvitationToRegister(int $user_id): bool
    {
        return (bool) $this->getDB()->single(
            "SELECT 1 FROM invitations WHERE created_user_id = ? AND status = ?",
            [$user_id, Invitation::STATUS_USED]
        );
    }

    public function searchInvitationUsedToRegister(int $user_id): ?Invitation
    {
        $row = $this->getDB()->row(
            'SELECT id, to_email, to_user_id, from_user_id, created_user_id FROM invitations WHERE created_user_id = ? AND status = ?',
            $user_id,
            Invitation::STATUS_USED
        );
        if (! $row) {
            return null;
        }

        return new Invitation($row['id'], $row['to_email'], $row['to_user_id'], $row['from_user_id'], $row['created_user_id']);
    }
}
