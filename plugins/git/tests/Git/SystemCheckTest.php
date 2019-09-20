<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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

require_once dirname(__FILE__).'/../bootstrap.php';

class Git_SystemCheckTest extends TuleapTestCase
{

    private $driver;
    private $gitgc;

    /** @var Git_SystemCheck */
    private $system_check;

    public function setUp()
    {
        parent::setUp();
        $this->driver               = mock('Git_GitoliteDriver');
        $this->gitgc                = mock('Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc');
        $this->system_event_manager = mock('Git_SystemEventManager');
        $logger                     = mock('Logger');
        $config_checker             = new PluginConfigChecker($logger);
        $plugin                     = mock('Plugin');

        $this->system_check = new Git_SystemCheck(
            $this->gitgc,
            $this->driver,
            $this->system_event_manager,
            $config_checker,
            $plugin
        );
    }

    public function itAsksToCheckAuthorizedKeys()
    {
        expect($this->driver)->checkAuthorizedKeys()->once();

        $this->system_check->process();
    }

    public function itAsksToCleanUpGitoliteAdminRepository()
    {
        expect($this->gitgc)->cleanUpGitoliteAdminWorkingCopy()->once();

        $this->system_check->process();
    }

    public function itAsksToCheckManifestFiles()
    {
        expect($this->system_event_manager)->queueGrokMirrorManifestCheck()->once();

        $this->system_check->process();
    }
}
