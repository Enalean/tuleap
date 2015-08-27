<?php
/**
 * Copyright (c) Enalean, 2015. All rights reserved
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

class RESTPhpWiki_DatabaseInitialization extends DatabaseInitialization {

    public function setUp() {
        $this->mysqli->select_db(ForgeConfig::get('sys_dbname'));
        $this->insertPhpWikiContent();
    }

    private function insertPhpWikiContent() {
        echo "Import PhpWiki plugin content \n";

        ForgeConfig::loadFromFile($this->getLocalIncPath());
        $tuleap_path  = ForgeConfig::get('codendi_dir') ? ForgeConfig::get('codendi_dir') : '/tuleap';
        $fixture_path = $tuleap_path.'/plugins/phpwiki/tests/_fixtures/rest';

        $queries = array(
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-group-list' INTO TABLE plugin_phpwiki_group_list",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-page' INTO TABLE plugin_phpwiki_page",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-nonempty' INTO TABLE plugin_phpwiki_nonempty",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-version' INTO TABLE plugin_phpwiki_version",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-recent' INTO TABLE plugin_phpwiki_recent",
        );

        foreach ($queries as $query) {
            $this->mysqli->real_query($query);
        }
    }
}