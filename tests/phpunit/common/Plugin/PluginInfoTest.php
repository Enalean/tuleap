<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class PluginInfoTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testPluginDescriptor(): void
    {
        $p  = \Mockery::spy(\Plugin::class);
        $pi = new PluginInfo($p);
        $pd = $pi->getPluginDescriptor();
        $this->assertInstanceOf(\PluginDescriptor::class, $pd);
        $this->assertEquals('', $pd->getFullName());
        $this->assertEquals('', $pd->getVersion());
        $this->assertEquals('', $pd->getDescription());
        $pi->setPluginDescriptor(new PluginDescriptor('TestPlugin', 'v1.0', 'A simple plugin, just for unit testing'));

        $pd = $pi->getPluginDescriptor();
        $this->assertEquals('TestPlugin', $pd->getFullName());
        $this->assertEquals('v1.0', $pd->getVersion());
        $this->assertEquals('A simple plugin, just for unit testing', $pd->getDescription());
    }

    public function testPropertyDescriptor(): void
    {
        $name_d1 = 'd1';
        $name_d2 = 'd2';
        $p  = \Mockery::spy(\Plugin::class);
        $pi = new class ($p) extends PluginInfo
        {
            public function addPropertyDescriptor($desc): void
            {
                $this->_addPropertyDescriptor($desc);
            }

            public function removePropertyDescriptor($desc): void
            {
                $this->_removePropertyDescriptor($desc);
            }
        };
        $d1 = \Mockery::spy(\PropertyDescriptor::class);
        $d1->shouldReceive('getName')->andReturns($name_d1);
        $d2 = \Mockery::spy(\PropertyDescriptor::class);
        $d2->shouldReceive('getName')->andReturns($name_d2);
        $d3 = \Mockery::spy(\PropertyDescriptor::class);
        $d3->shouldReceive('getName')->andReturns($name_d1);
        $pi->addPropertyDescriptor($d1);
        $pi->addPropertyDescriptor($d2);
        $pi->addPropertyDescriptor($d3);
        $expected = new Map();
        $expected->put($name_d2, $d2);
        $expected->put($name_d1, $d3);
        $descriptors = $pi->getpropertyDescriptors();
        $this->assertTrue($expected->equals($descriptors));

        $pi->removePropertyDescriptor($d3);
        $descriptors = $pi->getpropertyDescriptors();
        $this->assertFalse($expected->equals($descriptors));
        $this->assertEquals(1, $descriptors->size());
    }
}
