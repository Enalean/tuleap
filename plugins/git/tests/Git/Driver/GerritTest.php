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

    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var RemoteSshCommand
     */
    protected $ssh;

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('sys_default_domain', $this->host);

        $project = stub('Project')->getUnixName()->returns('firefox');

        $this->repository = aGitRepository()
            ->withProject($project)
            ->withNamespace('jean-claude')
            ->withName('dusse')
            ->build();

        $this->ssh    = mock('RemoteSshCommand');
        $this->driver = new Git_Driver_Gerrit($this->ssh);
    }

    public function tearDown() {
        parent::tearDown();
        Config::restore();
    }

    public function itExecutesTheCreateCommandOnTheGerritServer() {
        expect($this->ssh)->execute("gerrit create tuleap.example.com-firefox/jean-claude/dusse")->once();
        $this->driver->createProject($this->repository);
    }
}
?>
