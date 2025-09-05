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

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DataAccessObject;

class InvitationDao extends DataAccessObject implements InvitationByIdRetriever, InvitationByTokenRetriever, UsedInvitationRetriever, InvitationPurger, PendingInvitationsForProjectRetriever, PendingInvitationsWithdrawer, InvitationCreator, InvitationStatusUpdater
{
    public function __construct(
        private SplitTokenVerificationStringHasher $hasher,
        private InvitationInstrumentation $instrumentation,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function create(
        int $created_on,
        int $from_user_id,
        string $to_email,
        ?int $to_user_id,
        ?int $to_project_id,
        ?string $custom_message,
        SplitTokenVerificationString $verifier,
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'invitations',
            [
                'created_on'     => $created_on,
                'from_user_id'   => $from_user_id,
                'to_email'       => $to_user_id ? '' : $to_email,
                'to_user_id'     => $to_user_id,
                'to_project_id'  => $to_project_id,
                'custom_message' => $custom_message,
                'status'         => Invitation::STATUS_CREATING,
                'verifier'       => $this->hasher->computeHash($verifier),
            ]
        );
    }

    #[\Override]
    public function searchById(int $id): Invitation
    {
        $row = $this->getDB()->row('SELECT * FROM invitations WHERE id = ?', $id);
        if (! $row) {
            throw new InvitationNotFoundException();
        }

        return $this->instantiateFromRow($row);
    }

    #[\Override]
    public function markAsSent(int $id): void
    {
        $this->getDB()->update('invitations', ['status' => Invitation::STATUS_SENT], ['id' => $id]);
    }

    #[\Override]
    public function markAsError(int $id): void
    {
        $this->getDB()->update(
            'invitations',
            [
                'status'   => Invitation::STATUS_ERROR,
                'to_email' => '',
                'verifier' => '',
            ],
            ['id' => $id]
        );
    }

    /**
     * @throws InvalidInvitationTokenException|InvitationNotFoundException
     */
    #[\Override]
    public function searchBySplitToken(SplitToken $split_token): Invitation
    {
        $row = $this->getDB()->row(
            'SELECT *
            FROM invitations
            WHERE id = ?',
            $split_token->getID()
        );
        if (! $row) {
            throw new InvitationNotFoundException();
        }

        if (! $this->hasher->verifyHash($split_token->getVerificationString(), $row['verifier'])) {
            throw new InvalidInvitationTokenException(! empty($row['created_user_id']));
        }

        return $this->instantiateFromRow($row);
    }

    /**
     * @return Invitation[]
     */
    public function searchByCreatedUserId(int $user_id): array
    {
        return array_map(
            fn (array $row): Invitation => $this->instantiateFromRow($row),
            $this->getDB()->run(
                'SELECT * FROM invitations WHERE created_user_id = ? AND status IN (?, ?)',
                $user_id,
                Invitation::STATUS_COMPLETED,
                Invitation::STATUS_USED,
            )
        );
    }

    public function saveJustCreatedUserThanksToInvitation(
        string $to_email,
        int $just_created_user_id,
        ?int $used_invitation_id,
    ): void {
        $this->getDB()->run(
            "UPDATE invitations
                SET created_user_id = ?,
                    status = IF(id = ?, ?, ?),
                    to_email = '',
                    verifier = ''
                WHERE to_email = ?
                  AND status = ?
                  AND created_user_id IS NULL",
            $just_created_user_id,
            $used_invitation_id,
            Invitation::STATUS_USED,
            Invitation::STATUS_COMPLETED,
            $to_email,
            Invitation::STATUS_SENT,
        );
    }

    public function getInvitationsSentByUserForToday(int $user_id): int
    {
        $sql = 'SELECT count(*)
                FROM invitations
                WHERE from_user_id = ? AND DATE(FROM_UNIXTIME(created_on)) = CURDATE()';

        return (int) $this->getDB()->single($sql, [$user_id]);
    }

    public function hasUsedAnInvitationToRegister(int $user_id): bool
    {
        return (bool) $this->getDB()->single(
            'SELECT 1 FROM invitations WHERE created_user_id = ? AND status = ?',
            [$user_id, Invitation::STATUS_USED]
        );
    }

    #[\Override]
    public function searchInvitationUsedToRegister(int $user_id): ?Invitation
    {
        $row = $this->getDB()->row(
            'SELECT *
                FROM invitations
                WHERE created_user_id = ?
                  AND status = ?',
            $user_id,
            Invitation::STATUS_USED
        );
        if (! $row) {
            return null;
        }

        return $this->instantiateFromRow($row);
    }

    /**
     * @return Invitation[] Invitations that are removed
     */
    #[\Override]
    public function purgeObsoleteInvitations(\DateTimeImmutable $today, int $nb_days): array
    {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($today, $nb_days): array {
                $status_to_keep = EasyStatement::open()->in('status NOT IN (?*)', [Invitation::STATUS_USED, Invitation::STATUS_COMPLETED]);

                $obsolete_invitations = $db->run(
                    "SELECT *
                    FROM invitations
                    WHERE created_on < ?
                      AND created_user_id IS NULL
                      AND $status_to_keep",
                    $today->getTimestamp() - $nb_days * 24 * 3600,
                    ...$status_to_keep->values(),
                );

                if ($obsolete_invitations) {
                    $db->delete(
                        'invitations',
                        EasyStatement::open()->in('id IN (?*)', array_column($obsolete_invitations, 'id'))
                    );
                }

                $purged_invitations = [];
                foreach ($obsolete_invitations as $row) {
                    $purged_invitations[] = $this->instantiateFromRow($row);
                }

                return $purged_invitations;
            }
        );
    }

    public function removePendingInvitationsMadeByUser(int $user_id): void
    {
        $nb_deleted = $this->getDB()->delete(
            'invitations',
            [
                'from_user_id'    => $user_id,
                'status'          => Invitation::STATUS_SENT,
                'created_user_id' => null,
            ]
        );
        if ($nb_deleted > 0) {
            $this->instrumentation->incrementExpiredInvitations($nb_deleted);
        }
    }

    #[\Override]
    public function searchPendingInvitationsForProject(int $project_id): array
    {
        return array_map(
            fn (array $row): Invitation => $this->instantiateFromRow($row),
            $this->getDB()->run(
                'SELECT *
                    FROM invitations
                    WHERE to_project_id = ?
                      AND status = ?
                      AND to_email <> ""
                      AND created_user_id IS NULL
                    ORDER BY created_on',
                $project_id,
                Invitation::STATUS_SENT
            )
        );
    }

    #[\Override]
    public function withdrawPendingInvitationsForProject(string $to_email, int $project_id): void
    {
        $nb_deleted = $this->getDB()->delete(
            'invitations',
            [
                'to_project_id'   => $project_id,
                'to_email'        => $to_email,
                'created_user_id' => null,
                'status'          => Invitation::STATUS_SENT,
            ]
        );
        if ($nb_deleted > 0) {
            $this->instrumentation->incrementExpiredInvitations($nb_deleted);
        }
    }

    private function instantiateFromRow(array $row): Invitation
    {
        return new Invitation(
            $row['id'],
            $row['to_email'],
            $row['to_user_id'],
            $row['from_user_id'],
            $row['created_user_id'],
            $row['status'],
            $row['created_on'],
            $row['to_project_id'],
            $row['custom_message'],
        );
    }
}
