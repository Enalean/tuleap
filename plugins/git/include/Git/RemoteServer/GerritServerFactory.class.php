<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'GerritServer.class.php';
require_once 'Dao.class.php';
require_once GIT_BASE_DIR .'/GitRepository.class.php';

class Git_RemoteServer_GerritServerFactory {

    /** @var Git_RemoteServer_Dao */
    private $dao;
    
    public function __construct(Git_RemoteServer_Dao $dao) {
        $this->dao = $dao;
    }
    
    public function getServer(GitRepository $repository) {
        $id  = $repository->getRemoteServerId();
        $row = $this->dao->searchById($id)->getRow();
        if ($row) {
            return new Git_RemoteServer_GerritServer($row['host'], $row['port'], $row['login'], $row['identity_file']);
        }
        throw new GerritServerNotFoundException($id);
    }
}

class GerritServerNotFoundException extends Exception {
    public function __construct($id) {
        parent::__construct("No server found with the id: $id");
    }
}
?>
