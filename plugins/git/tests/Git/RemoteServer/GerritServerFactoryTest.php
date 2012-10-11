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

require_once dirname(__FILE__).'/../../../include/constants.php';
require_once GIT_BASE_DIR.'/Git/RemoteServer/GerritServerFactory.class.php';
require_once dirname(__FILE__).'/../../builders/aGitRepository.php';

class Git_RemoteServer_GerritServerFactoryTest extends TuleapTestCase {
    
    
    public function itThrowsAnExceptionIfThereIsNoSuchServer() {
        $id   = 34;
        $repo = stub('GitRepository')->getRemoteServerId()->returns($id);
        $dao     = stub('Git_RemoteServer_Dao')->searchById()->returnsEmptyDar();
        $factory = new Git_RemoteServer_GerritServerFactory($dao);
        try {
            $factory->getServer($repo);
            $this->fail('Should have thrown GerritServerNotFoundException');
        } catch (GerritServerNotFoundException $e) {
            $this->assertEqual($e->getMessage(), "No server found with the id: $id");
        }
    }
    
    public function itReturnsAGerritServer() {
        $host = 'g.tuleap.net';
        $port = 32915;
        $login = 'chuck';
        $identity_file = '/home/chuck/.ssh/id_rsa';
        $dar = array('id' => 99, 'host' => $host, 'port' => $port, 'login' => $login, 'identity_file' => $identity_file);
        $dao = stub('Git_RemoteServer_Dao')->searchById(99)->returnsDar($dar);
        $factory = new Git_RemoteServer_GerritServerFactory($dao);
        $repo = aGitRepository()->withRemoteServerId(99)->build();
        $server = $factory->getServer($repo);
        $this->assertEqual($server, new Git_RemoteServer_GerritServer($host, $port, $login, $identity_file));
    }
    
}

?>
