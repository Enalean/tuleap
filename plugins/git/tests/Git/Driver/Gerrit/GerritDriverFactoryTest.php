<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Git\Driver\Gerrit\GerritUnsupportedVersionDriver;

require_once __DIR__ .'/../../../bootstrap.php';

class GerritDriverFactoryTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $logger                      = new BackendLogger();
        $this->gerrit_driver_factory = new Git_Driver_Gerrit_GerritDriverFactory($logger);
    }

    public function itReturnsAGerritUnsupportedVersionDriverObjectIfServerIsIn25Version()
    {
        $server = new Git_RemoteServer_GerritServer(
            0,
            '',
            '',
            '',
            '',
            '',
            '',
            false,
            Git_RemoteServer_GerritServer::GERRIT_VERSION_2_5,
            '',
            '',
            ''
        );

        $this->assertIsA($this->gerrit_driver_factory->getDriver($server), GerritUnsupportedVersionDriver::class);
    }

    public function itReturnsAGerritUnsupportedVersionDriverObjectIfServerAsNoVersionSet()
    {
         $server = new Git_RemoteServer_GerritServer(
             0,
             '',
             '',
             '',
             '',
             '',
             '',
             false,
             '',
             '',
             '',
             ''
         );

        $this->assertIsA($this->gerrit_driver_factory->getDriver($server), GerritUnsupportedVersionDriver::class);
    }

    public function itReturnsAGerritDriverRESTObjectIfServerIsIn28Version()
    {
        $server = new Git_RemoteServer_GerritServer(
            0,
            '',
            '',
            '',
            '',
            '',
            '',
            false,
            Git_RemoteServer_GerritServer::GERRIT_VERSION_2_8_PLUS,
            '',
            '',
            ''
        );
        $this->assertIsA($this->gerrit_driver_factory->getDriver($server), 'Git_Driver_GerritREST');
    }
}
