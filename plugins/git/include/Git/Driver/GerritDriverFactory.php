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

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\Git\Driver\Gerrit\GerritUnsupportedVersionDriver;
use Tuleap\Git\Driver\GerritHTTPClientFactory;

/**
 * I build Git_Driver_Gerrit objects
 */
class Git_Driver_Gerrit_GerritDriverFactory
{
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /**
     * @var GerritHTTPClientFactory
     */
    private $gerrit_http_client_factory;
    /**
     * @var RequestFactoryInterface
     */
    private $request_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    public function __construct(
        GerritHTTPClientFactory $gerrit_http_client_factory,
        RequestFactoryInterface $request_factory,
        StreamFactoryInterface $stream_factory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->gerrit_http_client_factory = $gerrit_http_client_factory;
        $this->request_factory            = $request_factory;
        $this->stream_factory             = $stream_factory;
        $this->logger                     = $logger;
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
            return new Git_Driver_GerritREST(
                $this->gerrit_http_client_factory,
                $this->request_factory,
                $this->stream_factory,
                $this->logger
            );
        }

        return new GerritUnsupportedVersionDriver();
    }
}
