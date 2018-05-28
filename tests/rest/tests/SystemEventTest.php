<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SystemEvent\REST;

use RestBase;
use REST_TestDataBuilder;

require_once dirname(__FILE__).'/../lib/autoload.php';

/**
 * @group SystemEventTests
 */
class SystemEventTest extends RestBase
{

    protected function getResponse($request, $user_name = REST_TestDataBuilder::TEST_USER_1_NAME)
    {
        return parent::getResponse($request, $user_name);
    }

    public function testGET()
    {
        $response      = $this->getResponse($this->client->get('system_event'), REST_TestDataBuilder::ADMIN_USER_NAME);
        $response_json = $response->json();

        $system_event_01 = $response_json[0];
        $this->assertEquals($system_event_01['id'], 1);
        $this->assertEquals($system_event_01['type'], "SystemEvent_USER_CREATE");
        $this->assertEquals($system_event_01['owner'], "root");
    }

    public function testOptions()
    {
        $response = $this->getResponse($this->client->options('system_event'), REST_TestDataBuilder::ADMIN_USER_NAME);

        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
        $this->assertEquals($response->getStatusCode(), 200);
    }
}
