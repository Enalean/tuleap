<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SvnCore\Cache;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ParameterSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItSavesParameters(): void
    {
        $dao = \Mockery::spy(\Tuleap\SvnCore\Cache\ParameterDao::class);
        $dao->shouldReceive('save')->once()->andReturns(true);

        $event_manager = \Mockery::mock(\EventManager::class);
        $event_manager->shouldReceive('processEvent');

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(5, 5);
    }

    public function testItRejectsInvalidData(): void
    {
        $dao = \Mockery::spy(\Tuleap\SvnCore\Cache\ParameterDao::class);
        $dao->shouldReceive('save')->never();

        $event_manager = \Mockery::mock(\EventManager::class);
        $event_manager->shouldNotReceive('processEvent');

        $this->expectException(\Tuleap\SvnCore\Cache\ParameterMalformedDataException::class);

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(-1, 5);
        $parameter_saver->save(5, -1);
    }

    public function testItDealsWithDatabaseError(): void
    {
        $dao = \Mockery::spy(\Tuleap\SvnCore\Cache\ParameterDao::class);
        $dao->shouldReceive('save')->andReturns(false);

        $event_manager = \Mockery::mock(\EventManager::class);
        $event_manager->shouldNotReceive('processEvent');

        $this->expectException(\Tuleap\SvnCore\Cache\ParameterDataAccessException::class);

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(5, 5);
    }
}
