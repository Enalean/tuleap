<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * This class stores all the credentials needed to connect to MySQL
 */
class DataAccessCredentials {

    /** @var String */
    private $host;

    /** @var String */
    private $user;

    /** @var String */
    private $passwd;

    /** @var String */
    private $dbname;

    public function __construct($host, $user, $passwd, $dbname) {
        $this->host   = $host;
        $this->user   = $user;
        $this->passwd = $passwd;
        $this->dbname = $dbname;
    }

    public function getHost(){
        return $this->host;
    }

    public function getUser() {
        return $this->user;
    }

    public function getPassword() {
        return $this->passwd;
    }

    public function getDatabaseName() {
        return $this->dbname;
    }
}