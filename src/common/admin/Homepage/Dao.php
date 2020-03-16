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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

class Admin_Homepage_Dao extends DataAccessObject
{

    /** @return DataAccessResult */
    public function searchHeadlines()
    {
        $sql = "SELECT * FROM homepage_headline";

        return $this->retrieve($sql);
    }

    /** @return string|null */
    public function getHeadlineByLanguage($language_id)
    {
        $language_id = $this->da->quoteSmart($language_id);

        $sql = "SELECT * FROM homepage_headline WHERE language_id = $language_id";

        $row = $this->retrieve($sql)->getRow();

        return $row['headline'];
    }

    /** @return bool */
    public function save(array $headlines)
    {
        $values = array();

        foreach ($headlines as $language_id => $headline) {
            $language_id = $this->da->quoteSmart($language_id);
            $headline    = $this->da->quoteSmart($headline);
            $values[] = "($language_id, $headline)";
        }

        $sql = "REPLACE INTO homepage_headline(language_id, headline) VALUES " . implode(', ', $values);

        return $this->update($sql);
    }
}
