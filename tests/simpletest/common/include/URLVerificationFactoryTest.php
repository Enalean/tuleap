<?php
/**
 * Copyright (c) Enalean 2018. All Rights Reserved.
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

class Plugin_URLVerification extends URLVerification
{
}

class URLVerificationFactoryTest extends TuleapTestCase
{
    function testGetUrlVerifictionNoPluginResponse()
    {
        $event_manager = \Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')->with('url_verification_instance', \Mockery::any())->once();

        $urlVerif = new URLVerificationFactory($event_manager);

        $this->assertIsA($urlVerif->getURLVerification([]), URLVerification::class);
    }

    function testGetUrlVerifictionWithPluginResponse()
    {
        $event_manager = \Mockery::mock(EventManager::class);
        $event_manager->shouldReceive('processEvent')->with('url_verification_instance', \Mockery::on(function (array $args) {
            $args['url_verification'] = new Plugin_URLVerification();
            return true;
        }))->once();

        $urlVerif = new URLVerificationFactory($event_manager);

        $this->assertIsA($urlVerif->getURLVerification([]), Plugin_URLVerification::class);
    }
}
