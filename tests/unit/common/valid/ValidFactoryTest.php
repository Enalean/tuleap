<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\valid;

use ValidFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ValidFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetInstance(): void
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $this->assertInstanceOf('Valid', ValidFactory::getInstance($v));

        $this->assertInstanceOf('Valid_String', ValidFactory::getInstance('string'));
        $this->assertInstanceOf('Valid_UInt', ValidFactory::getInstance('uint'));
        $this->assertNull(ValidFactory::getInstance('machinbidulechose'));

        $key = bin2hex(random_bytes(16));
        $w = ValidFactory::getInstance('string', $key);
        $this->assertEquals($key, $w->getKey());
    }
}
