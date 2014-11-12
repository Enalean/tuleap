<?php
/**
  * Copyright (c) Enalean, 2014. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

class ElasticSearch_ClientConfig {

    const TIMEOUT = 5;

    private $client_path;
    private $server_host;
    private $server_port;
    private $server_user;
    private $server_password;
    private $request_timeout;

    public function __construct(
        $client_path,
        $server_host,
        $server_port,
        $server_user,
        $server_password,
        $request_timeout
    ) {
        $this->client_path     = $client_path;
        $this->server_host     = $server_host;
        $this->server_port     = $server_port;
        $this->server_user     = $server_user;
        $this->server_password = $server_password;
        $this->request_timeout = $request_timeout;
    }

    public function getClientPath() {
        return $this->client_path;
    }

    public function getServerHost() {
        return $this->server_host;
    }

    public function getServerPort() {
        return $this->server_port;
    }

    public function getServerUser() {
        return $this->server_user;
    }

    public function getServerPassword() {
        return $this->server_password;
    }

    public function getRequestTimeout() {
        if (! $this->request_timeout && $this->request_timeout !== '0') {
            return self::TIMEOUT;
        }

        return (int) $this->request_timeout;
    }
}
