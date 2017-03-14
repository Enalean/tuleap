<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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
        ForgeConfig::store();
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itAddsTheRequestStringSentInCurlGetInfo() {
        $client = new Http_Client();
        $this->assertTrue($client->getOption(CURLINFO_HEADER_OUT));
    }

    public function itUsesTheProxyConfig() {
        ForgeConfig::set('sys_proxy', 'le_host:le_port');
        $client = new Http_Client();
        $this->assertEqual($client->getOption(CURLOPT_PROXY), 'le_host:le_port');
    }

    public function itDoesNotSetProxyOptionWhenProxyConfigDoesNotExist() {
        ForgeConfig::set('sys_proxy', '');
        $client = new Http_Client();
        $this->assertEqual($client->getOption(CURLOPT_PROXY), '');
    }

    public function itDoesNotOutputDirectlyTheResponse() {
        $client = new Http_Client();
        $this->assertTrue($client->getOption(CURLOPT_RETURNTRANSFER));
    }

    public function itFailsOnError() {
        $client = new Http_Client();
        $this->assertTrue($client->getOption(CURLOPT_FAILONERROR));
    }

    public function itTimeoutsInFiveSeconds() {
        $client = new Http_Client();
        $this->assertEqual($client->getOption(CURLOPT_TIMEOUT), 5);
    }

    public function itRetrievesStatusCodeAndReasonPhrase()
    {
        $client = partial_mock('Http_Client', array('getLastResponse', 'getOption'));

        stub($client)->getOption(CURLOPT_HEADER)->returns(1);
        stub($client)->getLastResponse()->returns('HTTP/1.1 200 OK
Date: Tue, 14 Mar 2017 15:15:05 GMT
OtherHeader: Tuleap');

        $this->assertEqual($client->getStatusCodeAndReasonPhrase(), '200 OK');
    }

    public function itDoesNotRetrieveStatusCodeAndReasonPhraseWhenHeadersHasNotBeenRequested()
    {
        $client = partial_mock('Http_Client', array('getLastResponse', 'getOption'));

        stub($client)->getOption(CURLOPT_HEADER)->returns(0);
        stub($client)->getLastResponse()->returns('HTTP/1.1 200 OK
Date: Tue, 14 Mar 2017 15:15:05 GMT
OtherHeader: Tuleap');

        $this->assertEqual($client->getStatusCodeAndReasonPhrase(), null);
    }

    public function itDoesNotRetrieveStatusCodeAndReasonPhraseWhenThereIsNotLastResponse()
    {
        $client = partial_mock('Http_Client', array('getLastResponse', 'getOption'));

        stub($client)->getOption(CURLOPT_HEADER)->returns(1);
        stub($client)->getLastResponse()->returns(null);

        $this->assertEqual($client->getStatusCodeAndReasonPhrase(), null);
    }
}
