<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for UserPreferences
 */
class UserPreferencesDao extends DataAccessObject
{

    public function __construct($da = null)
    {
        parent::__construct($da);
    }

    /**
     * Search user preferences by user id and preference name
     * @param int $user_id
     * @param string $preference_name
     * @return DataAccessResult
     */
    public function search($user_id, $preference_name)
    {
        $sql = sprintf(
            "SELECT * FROM user_preferences WHERE user_id = %d AND preference_name = %s",
            $this->da->escapeInt($user_id),
            $this->da->quoteSmart($preference_name)
        );
        return $this->retrieve($sql);
    }

    /**
     * Set a preference for the user
     *
     * @param int $user_id
     * @param string $preference_name
     * @param string $preference_value
     * @return bool
     */
    public function set($user_id, $preference_name, $preference_value)
    {
        $sql = sprintf(
            "INSERT INTO user_preferences (user_id, preference_name, preference_value) VALUES (%d, %s, %s)
                        ON DUPLICATE KEY UPDATE preference_value = %s",
            $this->da->escapeInt($user_id),
            $this->da->quoteSmart($preference_name),
            $this->da->quoteSmart($preference_value),
            $this->da->quoteSmart($preference_value)
        );
        return $this->update($sql);
    }

    /**
     * Delete a preference
     */
    public function delete($user_id, $preference_name)
    {
        $sql = sprintf(
            "DELETE FROM user_preferences WHERE user_id = %d AND preference_name = %s",
            $this->da->escapeInt($user_id),
            $this->da->quoteSmart($preference_name)
        );
        return $this->update($sql);
    }

    public function deleteByPreferenceNameAndValue($preference_name, $preference_value)
    {
        $preference_name  = $this->da->quoteSmart($preference_name);
        $preference_value = $this->da->quoteSmart($preference_value);
        $sql = "DELETE FROM user_preferences
                WHERE preference_name = $preference_name
                  AND preference_value = $preference_value";
        return $this->update($sql);
    }
}
