<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

/**
 * I build Git_Driver_Gerrit objects
 */
class Git_Driver_Gerrit_GerritDriverFactory
{

    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Builds the Gerrit Driver regarding the gerrit server version
     *
     * @param Git_RemoteServer_GerritServer $server The gerrit server
     *
     * @return Git_Driver_Gerrit
     */
    public function getDriver(Git_RemoteServer_GerritServer $server)
    {
        if ($server->getGerritVersion() === Git_RemoteServer_GerritServer::GERRIT_VERSION_2_8_PLUS) {
            require_once '/usr/share/php/Guzzle/autoload.php';
            return new Git_Driver_GerritREST(
                new Guzzle\Http\Client('', array('ssl.certificate_authority' => 'system')),
                $this->logger,
                $server->getAuthType()
            );
        }

        return new GerritUnsupportedVersionDriver();
    }
}
