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
 * @codingStandardsIgnoreFile
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/_fixtures/DatPlugin.php';

class PluginProxyTest extends TestCase
{
    public function testItDoesNothingWhenEventIsNotRegistered()
    {
        $proxy = new PluginProxy('DatPlugin', -2, []);

        $proxy->addListener('dat_event', 'setRandomInteger', false);

        $params = array();
        $proxy->processEvent('stuff', $params);
        $this->assertCount(0, $params);
    }

    public function testItCallOriginalPluginOnProcessEvent()
    {
        $proxy = new PluginProxy('DatPlugin', -2, []);

        $proxy->addListener('dat_event', 'setRandomInteger', false);

        $value = null;
        $params = array(
            'random' => &$value
        );
        $proxy->processEvent('dat_event', $params);
        $this->assertEquals(4, $value);
    }

    public function testItKeepOneInstanceOfThePlugin()
    {
        $proxy = new PluginProxy('DatPlugin', -2, []);

        $proxy->addListener('increment', 'increment', false);


        $this->assertEquals(0, $this->getCounterValue($proxy));
        $this->assertEquals(1, $this->getCounterValue($proxy));
    }

    public function testItKeepEachProxyIndependant()
    {
        $proxy_1 = new PluginProxy('DatPlugin', -2, []);
        $proxy_1->addListener('increment', 'increment', false);

        $proxy_2 = new PluginProxy('DatPlugin', 50, []);
        $proxy_2->addListener('increment', 'increment', false);

        $this->assertEquals(0, $this->getCounterValue($proxy_1));
        $this->assertEquals(0, $this->getCounterValue($proxy_2));
    }

    private function getCounterValue(PluginProxy $proxy)
    {
        $value = null;
        $params = array(
            'counter' => &$value
        );
        $proxy->processEvent('increment', $params);
        return $value;
    }


    public function testItInjectPluginNameAndRestrictionStatus()
    {
        $proxy = new PluginProxy(
            'DatPlugin',
            2,
            [
                2 => new \Tuleap\Plugin\PluginProxyInjectedData(['name' => 'dat-plugin', 'prj_restricted' => 0])
            ]
        );

        $proxy->addListener('dat_event', 'gatherNameAndIsRestricted', false);

        $name = null;
        $is_restricted = null;
        $params = [
            'name' => &$name,
            'is_restricted' => &$is_restricted,
        ];
        $proxy->processEvent('dat_event', $params);

        $this->assertEquals('dat-plugin', $params['name']);
        $this->assertEquals(false, $params['is_restricted']);
    }
}
