<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once '_fixtures/DatPlugin.php';

class PluginProxyTest extends TuleapTestCase {

    public function itDoesNothingWhenEventIsNotRegistered() {
        $proxy = new PluginProxy('DatPlugin', -2);

        $proxy->addListener('dat_event', 'setRandomInteger', false);

        $params = array();
        $proxy->processEvent('stuff', $params);
        $this->assertArrayEmpty($params);
    }

    public function itCallOriginalPluginOnProcessEvent() {
        $proxy = new PluginProxy('DatPlugin', -2);

        $proxy->addListener('dat_event', 'setRandomInteger', false);

        $value = null;
        $params = array(
            'random' => &$value
        );
        $proxy->processEvent('dat_event', $params);
        $this->assertEqual($value, 4);
    }

    public function itKeepOneInstanceOfThePlugin() {
        $proxy = new PluginProxy('DatPlugin', -2);

        $proxy->addListener('increment', 'increment', false);


        $this->assertEqual($this->getCounterValue($proxy), 0);
        $this->assertEqual($this->getCounterValue($proxy), 1);
    }

    public function itKeepEachProxyIndependant() {
        $proxy_1 = new PluginProxy('DatPlugin', -2);
        $proxy_1->addListener('increment', 'increment', false);

        $proxy_2 = new PluginProxy('DatPlugin', 50);
        $proxy_2->addListener('increment', 'increment', false);

        $this->assertEqual($this->getCounterValue($proxy_1), 0);
        $this->assertEqual($this->getCounterValue($proxy_2), 0);
    }

    private function getCounterValue(PluginProxy $proxy) {
        $value = null;
        $params = array(
            'counter' => &$value
        );
        $proxy->processEvent('increment', $params);
        return $value;
    }
}
