<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/URLVerificationFactory.class.php');
Mock::generatePartial(
    'URLVerificationFactory',
    'URLVerificationFactoryTestVersion',
    array('getEventManager')
);

class Plugin_URLVerification extends URLVerification {}

require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

class MockEM4Url extends MockEventManager {
   function processEvent($event, $params) {
       foreach(parent::processEvent($event, $params) as $key => $value) {
           $params[$key] = $value;
       }
   }
}

class URLVerificationFactoryTest extends TuleapTestCase {

    function testGetUrlVerifictionNoPluginResponse() {
        $em = new MockEM4Url($this);
        $em->setReturnValue('processEvent', array());

        $urlVerif = new URLVerificationFactoryTestVersion($this);
        $urlVerif->setReturnValue('getEventManager', $em);
        $server = array();

        $this->assertEqual(($urlVerif->getURLVerification($server) instanceof URLVerification), true);
    }

    function testGetUrlVerifictionWithPluginResponse() {
        $em = new MockEM4Url($this);
        $plugin_URLVerification = new Plugin_URLVerification();
        $em->setReturnValue('processEvent', array('url_verification' => $plugin_URLVerification));

        $urlVerif = new URLVerificationFactoryTestVersion($this);
        $urlVerif->setReturnValue('getEventManager', $em);
        $server = array();

        $this->assertEqual(($urlVerif->getURLVerification($server) instanceof Plugin_URLVerification), true);
    }

}
?>