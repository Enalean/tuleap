<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAuthn\Challenge;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;
use function Psl\Type\string;

final class WebAuthnChallengeDao extends DataAccessObject implements SaveWebAuthnChallenge, RetrieveWebAuthnChallenge
{
    private const VALIDITY_TIME_IN_SECONDS = 60 * 2;

    #[\Override]
    public function saveChallenge(int $user_id, string $challenge): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($user_id, $challenge) {
                $this->deleteExpiredChallenge();

                $db->insertOnDuplicateKeyUpdate(
                    'webauthn_challenge',
                    [
                        'user_id' => $user_id,
                        'challenge' => $challenge,
                        'expiration_date' => (new \DateTimeImmutable())->getTimestamp() + self::VALIDITY_TIME_IN_SECONDS,
                    ],
                    ['challenge', 'expiration_date']
                );
            }
        );
    }

    #[\Override]
    public function searchChallenge(int $user_id): Option
    {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($user_id) {
                $this->deleteExpiredChallenge();

                $sql = 'SELECT challenge
                        FROM webauthn_challenge
                        WHERE user_id = ?';
                $row = $db->row($sql, $user_id);

                if ($row) {
                    $db->delete('webauthn_challenge', ['user_id' => $user_id]);

                    return Option::fromValue($row['challenge']);
                }

                return Option::nothing(string());
            }
        );
    }

    private function deleteExpiredChallenge(): void
    {
        $this->getDB()->run(
            'DELETE FROM webauthn_challenge WHERE expiration_date <= ?',
            (new \DateTimeImmutable())->getTimestamp()
        );
    }
}
