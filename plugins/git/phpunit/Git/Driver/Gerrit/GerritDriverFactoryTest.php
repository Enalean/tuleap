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

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Tuleap\Git\Driver\Gerrit\GerritUnsupportedVersionDriver;

require_once __DIR__ . '/../../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GerritDriverFactoryTest extends TestCase
{

    /**
     * @var Git_Driver_Gerrit_GerritDriverFactory
     */
    private $gerrit_driver_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $logger                      = BackendLogger::getDefaultLogger();
        $this->gerrit_driver_factory = new Git_Driver_Gerrit_GerritDriverFactory(
            new \Tuleap\Git\Driver\GerritHTTPClientFactory(\Tuleap\Http\HttpClientFactory::createClient()),
            \Tuleap\Http\HTTPFactoryBuilder::requestFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            $logger
        );
    }

    public function testItReturnsAGerritUnsupportedVersionDriverObjectIfServerIsIn25Version(): void
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
        );

        $this->assertInstanceOf(
            GerritUnsupportedVersionDriver::class,
            $this->gerrit_driver_factory->getDriver($server)
        );
    }

    public function testItReturnsAGerritUnsupportedVersionDriverObjectIfServerAsNoVersionSet(): void
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
         );



        $this->assertInstanceOf(
            GerritUnsupportedVersionDriver::class,
            $this->gerrit_driver_factory->getDriver($server)
        );
    }

    public function testItReturnsAGerritDriverRESTObjectIfServerIsIn28Version(): void
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
        );

        $this->assertInstanceOf(
            Git_Driver_GerritREST::class,
            $this->gerrit_driver_factory->getDriver($server)
        );
    }
}
