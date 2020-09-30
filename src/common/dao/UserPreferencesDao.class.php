<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class UserPreferencesDao extends \Tuleap\DB\DataAccessObject
{
    /**
     * Search user preferences by user id and preference name
     */
    public function search(int $user_id, string $preference_name): array
    {
        $sql = 'SELECT * FROM user_preferences WHERE user_id = ? AND preference_name = ?';

        $result = $this->getDB()->row($sql, $user_id, $preference_name);

        return is_array($result) ? $result : [];
    }

    public function set(int $user_id, string $preference_name, string $preference_value): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'user_preferences',
            [
                'user_id'          => $user_id,
                'preference_name'  => $preference_name,
                'preference_value' => $preference_value
            ],
            [
                'preference_value'
            ]
        );
    }

    public function delete(int $user_id, string $preference_name): void
    {
        $this->getDB()->delete(
            'user_preferences',
            [
                'user_id'         => $user_id,
                'preference_name' => $preference_name
            ]
        );
    }

    public function deleteByPreferenceNameAndValue(string $preference_name, string $preference_value): void
    {
        $this->getDB()->delete(
            'user_preferences',
            [
                'preference_name' => $preference_name,
                'preference_value' => $preference_value
            ]
        );
    }

    public function deletePreferenceForAllUsers(string $preference_name): void
    {
        $this->getDB()->delete('user_preferences', ['preference_name' => $preference_name]);
    }
}
