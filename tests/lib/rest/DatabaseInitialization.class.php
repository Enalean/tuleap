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

class REST_DatabaseInitialization extends DatabaseInitialization {

    public function setUp() {
        parent::setUp();

        $this->insertPhpWikiContent();
    }

    private function insertPhpWikiContent() {
        echo "Import PhpWiki content \n";

        ForgeConfig::loadFromFile($this->getLocalIncPath());
        $tuleap_path  = ForgeConfig::get('codendi_dir') ? ForgeConfig::get('codendi_dir') : '/tuleap';
        $fixture_path = $tuleap_path.'/tests/rest/_fixtures/phpwiki';

        $queries = array(
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-group-list' INTO TABLE wiki_group_list",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-page' INTO TABLE wiki_page",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-nonempty' INTO TABLE wiki_nonempty",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-version' INTO TABLE wiki_version",
            "LOAD DATA LOCAL INFILE '".$fixture_path."/rest-test-wiki-recent' INTO TABLE wiki_recent",
        );

        foreach ($queries as $query) {
            $this->mysqli->real_query($query);
        }
    }
}