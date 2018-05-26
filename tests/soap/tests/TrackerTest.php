<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tests\SOAP;

use SOAP_TestDataBuilder;
use SOAPBase;
use SoapClient;

require_once __DIR__.'/../lib/autoload.php';

class TrackerTest extends SOAPBase
{
    /**
     * @var SoapClient
     */
    private $plugin_tracker_client;

    private $plugin_tracker_url;

    public function setUp()
    {
        parent::setUp();

        $this->plugin_tracker_url = 'https://localhost/plugins/tracker/soap/?wsdl';

        $this->plugin_tracker_client = new SoapClient(
            $this->plugin_tracker_url,
            array('cache_wsdl' => WSDL_CACHE_NONE, 'stream_context' => $this->context)
        );

        $_SERVER['SERVER_NAME'] = $this->server_name;
        $_SERVER['SERVER_PORT'] = $this->server_port;
        $_SERVER['SCRIPT_NAME'] = $this->base_wsdl;
    }

    public function tearDown()
    {
        unset($_SERVER['SERVER_NAME']);
        unset($_SERVER['SERVER_PORT']);
        unset($_SERVER['SCRIPT_NAME']);

        parent::tearDown();
    }

    public function testGetTrackers()
    {
        $session_hash = $this->getSessionHash();

        $response = $this->plugin_tracker_client->getTrackerList(
            $session_hash,
            SOAP_TestDataBuilder::PROJECT_PLUGIN_TRACKER_ID
        );

        // These trackers came from the migration of TV3 to TV5 at project creation
        $this->assertCount(5, $response);
        $this->assertEquals('bug', $response[0]->item_name);
        $this->assertEquals('rel', $response[1]->item_name);
        $this->assertEquals('story', $response[2]->item_name);
        $this->assertEquals('SR', $response[3]->item_name);
        $this->assertEquals('task', $response[4]->item_name);
    }
}
