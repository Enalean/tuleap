<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

class SessionDao extends \Tuleap\DB\DataAccessObject
{
    public function create($user_id, $token, $ip_address, $current_time, string $user_agent): int
    {
        return (int) $this->getDB()->insertReturnId(
            'session',
            [
                'user_id' => $user_id,
                'session_hash' => $token,
                'ip_addr' => $ip_address,
                'time' => $current_time,
                'user_agent' => $user_agent,
            ]
        );
    }

    public function searchById($id, $current_time, $session_lifetime): ?array
    {
        return $this->getDB()->row('SELECT * FROM session WHERE id = ? AND time + ? > ?', $id, $session_lifetime, $current_time);
    }

    public function updateUserAgentByID(int $id, string $user_agent): void
    {
        $this->getDB()->run('UPDATE session SET user_agent = ? WHERE id = ?', $user_agent, $id);
    }

    /**
     * @return int the number of active sessions
     */
    public function count($current_time, $session_lifetime): int
    {
        return $this->getDB()->single(
            'SELECT COUNT(*) AS nb FROM session WHERE time + ? > ?',
            [$session_lifetime, $current_time]
        );
    }

    /**
     * @psalm-return array{"user_agent": string, nb: int}[]
     */
    public function countUserAgentsOfActiveSessions(int $current_time, int $session_lifetime): array
    {
        return $this->getDB()->run(
            'SELECT user_agent, COUNT(id) AS nb FROM session WHERE time + ? > ? GROUP BY user_agent',
            $session_lifetime,
            $current_time
        );
    }

    public function deleteSessionById($id): void
    {
        $this->getDB()->run('DELETE FROM session WHERE id = ?', $id);
    }

    public function deleteSessionByUserId($user_id): void
    {
        $this->getDB()->run('DELETE FROM session WHERE user_id = ?', $user_id);
    }

    public function deleteAllSessionsByUserIdButTheCurrentOne($user_id, $current_session_id): void
    {
        $this->getDB()->run('DELETE FROM session WHERE user_id = ? AND id != ?', $user_id, $current_session_id);
    }

    public function deleteAll(): void
    {
        $this->getDB()->run('TRUNCATE TABLE session');
    }

    public function deleteExpiredSession(int $current_time, int $session_lifetime): void
    {
        $this->getDB()->run('DELETE FROM session WHERE time + ? < ?', $session_lifetime, $current_time);
    }
}
