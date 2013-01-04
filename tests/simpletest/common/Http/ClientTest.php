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

require_once 'common/Http/Client.class.php';

class Http_ClientTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        Config::store();
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
    }

    public function itAddsTheRequestStringSentInCurlGetInfo() {
        $client = new Http_Client();
        $this->assertTrue($client->getOption(CURLINFO_HEADER_OUT));
    }

    public function itUsesTheProxyConfig() {
        Config::set('sys_proxy', 'le_host:le_port');
        $client = new Http_Client();
        $this->assertEqual($client->getOption(CURLOPT_PROXY), 'le_host:le_port');
    }

    public function itDoesNotSetProxyOptionWhenProxyConfigDoesNotExist() {
        Config::set('sys_proxy', '');
        $client = new Http_Client();
        $this->assertEqual($client->getOption(CURLOPT_PROXY), '');
    }
}
?>
