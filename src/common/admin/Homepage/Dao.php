<?php
/**
  * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Admin_Homepage_Dao extends DataAccessObject {

    /** @return DataAccessResult */
    public function searchHeadlines() {
        $sql = "SELECT * FROM homepage_headline";

        return $this->retrieve($sql);
    }

    /** @return string */
    public function getHeadlineByLanguage($language_id) {
        $language_id = $this->da->quoteSmart($language_id);

        $sql = "SELECT * FROM homepage_headline WHERE language_id = $language_id";

        $row = $this->retrieve($sql)->getRow();

        return $row['headline'];
    }

    /** @return boolean */
    public function save(array $headlines) {
        $values = array();

        foreach ($headlines as $language_id => $headline) {
            $language_id = $this->da->quoteSmart($language_id);
            $headline    = $this->da->quoteSmart($headline);
            $values[] = "($language_id, $headline)";
        }

        $sql = "REPLACE INTO homepage_headline(language_id, headline) VALUES ". implode(', ', $values);

        return $this->update($sql);
    }

    /** @return bool */
    public function isStandardHomepageUsed() {
        $sql = "SELECT * FROM homepage";

        $row = $this->retrieve($sql)->getRow();

        return (bool)$row['use_standard_homepage'];
    }

    public function useStandardHomepage() {
        $this->resetUsageOfStandardHomepage();

        $sql = "REPLACE INTO homepage (use_standard_homepage, display_platform_statistics) VALUES (1, 1)";

        return $this->update($sql);
    }

    public function areStatisticsDisplayedOnHomePage()
    {
        $sql = "SELECT * FROM homepage";

        $row = $this->retrieve($sql)->getRow();

        return (bool)$row['display_platform_statistics'];
    }

    public function toggleStatisticsOnHomePage($display_statistics)
    {
        $display_statistics = $this->da->escapeInt($display_statistics);
        $sql = "UPDATE homepage SET display_platform_statistics = $display_statistics";

        return $this->update($sql);
    }

    private function resetUsageOfStandardHomepage() {
        $sql = "TRUNCATE TABLE homepage";

        return $this->update($sql);
    }
}
