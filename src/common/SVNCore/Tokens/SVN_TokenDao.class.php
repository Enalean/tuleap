<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class SVN_TokenDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * @psalm-return list<array{id: int, user_id: int, token: string, generated_date: int, last_usage: int, last_ip: string, comment: string }>
     */
    public function getSVNTokensForUser(int $user_id): array
    {
        $sql = "SELECT id, user_id, token, generated_date, last_usage, last_ip, comment
                FROM svn_token
                WHERE user_id = ?";

        return $this->getDB()->run($sql, $user_id);
    }

    public function generateSVNTokenForUser(int $user_id, string $token, string $comment): void
    {
        $generated_date = time();

        $sql = "INSERT INTO svn_token (user_id, token, generated_date, comment)
                VALUES (?, ?, ?, ?)";

        $this->getDB()->run($sql, $user_id, $token, $generated_date, $comment);
    }

    public function deleteSVNTokensForUser(int $user_id, array $tokens_to_be_deleted): void
    {
        if (count($tokens_to_be_deleted) === 0) {
            return;
        }

        $sql_conditions = \ParagonIE\EasyDB\EasyStatement::open()
            ->with('user_id = ?', $user_id)
            ->andIn('id IN (?*)', $tokens_to_be_deleted);

        $this->getDB()->safeQuery("DELETE FROM svn_token WHERE $sql_conditions", $sql_conditions->values());
    }
}
