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

class SOAP_DatabaseInitialization extends DatabaseInitialization {

    protected function initDb() {
        echo "Create database structure \n";

        $this->forceCreateDatabase();
        $this->mysqlLoadFile('src/db/mysql/database_structure.sql');
        $this->mysqlLoadFile('src/db/mysql/database_initvalues.sql');
        $this->mysqlLoadFile('src/db/mysql/trackerv3structure.sql');
        $this->mysqlLoadFile('src/db/mysql/trackerv3values.sql');
        $this->mysqlLoadFile('plugins/docman/db/install.sql');
    }
}