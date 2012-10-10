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
require_once dirname(__FILE__).'/../../builders/aGitRepository.php';
require_once GIT_BASE_DIR . '/Git/Driver/Gerrit.class.php';
require_once 'common/include/Config.class.php';
class Git_Driver_Gerrit_createTest extends TuleapTestCase {
    
    protected $host = 'tuleap.example.com';
    
    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('sys_default_domain', $this->host);
    }

    public function tearDown() {
        parent::tearDown();
        Config::restore();
    }
    
    public function itExecutesTheCreateCommandOnTheGerritServer() {
        $ssh    = mock('RemoteSshCommand');
        $driver = new Git_Driver_Gerrit($ssh);
        $repo = aGitRepository()
                ->withProject(aMockProject()->withShortName('Firefox')->build())
                ->withName('dusse')
                ->withNamespace('jean-claude')
                ->build();
        expect($ssh)->execute("gerrit create $this->host-Firefox/jean-claude/dusse");
        $driver->createProject($repo);
    }
}
?>
