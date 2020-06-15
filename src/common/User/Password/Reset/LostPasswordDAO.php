<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class LostPasswordDAO extends DataAccessObject
{
    private const MIN_DELAY_BETWEEN_LOST_PASSWORD_TOKEN_CREATION_SECONDS = 10 * 60;

    public function create(int $user_id, string $verifier, int $current_time): ?int
    {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($user_id, $verifier, $current_time): ?int {
                $recently_created_code = $db->cell(
                    "SELECT COUNT(*) FROM user_lost_password WHERE user_id = ? AND ? < creation_date",
                    $user_id,
                    $current_time - self::MIN_DELAY_BETWEEN_LOST_PASSWORD_TOKEN_CREATION_SECONDS
                );

                if ($recently_created_code > 0) {
                    return null;
                }

                $db->run(
                    "INSERT INTO user_lost_password(user_id, verifier, creation_date) VALUES (?,?,?)",
                    $user_id,
                    $verifier,
                    $current_time,
                );

                return (int) $this->getDB()->lastInsertId();
            }
        );
    }

    /**
     * @return array|null
     * @psalm-return array{user_id:int,verifier:string,creation_date:int}|null
     */
    public function getTokenInformationById(int $id): ?array
    {
        return $this->getDB()->row(
            "SELECT user_id, verifier, creation_date
            FROM user_lost_password
            WHERE id = ?",
            $id,
        );
    }

    public function deleteTokensByUserId(int $user_id): void
    {
        $this->getDB()->run("DELETE FROM user_lost_password WHERE user_id = ?", $user_id);
    }
}
