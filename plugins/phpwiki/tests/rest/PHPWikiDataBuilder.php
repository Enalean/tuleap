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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/autoload.php';
require_once dirname(__FILE__).'/DatabaseInitialization.class.php';

class PHPWikiDataBuilder extends REST_TestDataBuilder {
    const PHPWIKI_PAGE_ID          = 6097;
    const PHPWIKI_SPACE_PAGE_ID    = 6100;

    public function setUp() {
        $this->installPlugin();
        $this->activatePlugin('phpwiki');
        $this->enablePlugin();
        $this->createPhpWikiPluginData();
    }

    private function installPlugin() {
        $dbtables = new DBTablesDAO();
        $dbtables->updateFromFile(dirname(__FILE__).'/../../db/install.sql');
    }

    private function enablePlugin() {
        $dao        = new DataAccessObject();
        $project_id = $dao->getDa()->escapeInt(TestDataBuilder::PROJECT_PUBLIC_ID);
        $dao->update('UPDATE service SET is_used = 1 WHERE short_name = "plugin_phpwiki" AND group_id = ' . $project_id);
    }

    private function createPhpWikiPluginData() {
        $database_init = new RESTPhpWiki_DatabaseInitialization();
        $database_init->setUp();
    }
}