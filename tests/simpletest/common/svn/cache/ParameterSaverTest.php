<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use TuleapTestCase;

class ParameterSaverTest extends TuleapTestCase
{
    public function itSavesParameters()
    {
        $dao = mock('Tuleap\SvnCore\Cache\ParameterDao');
        stub($dao)->save()->returns(true);
        $dao->expectOnce('save');

        $event_manager = \Mockery::mock(\EventManager::class);
        $event_manager->shouldReceive('processEvent');

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(5, 5);
    }

    public function itRejectsInvalidData()
    {
        $dao = mock('Tuleap\SvnCore\Cache\ParameterDao');
        $dao->expectNever('save');

        $event_manager = \Mockery::mock(\EventManager::class);
        $event_manager->shouldNotReceive('processEvent');

        $this->expectException('Tuleap\SvnCore\Cache\ParameterMalformedDataException');

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(-1, 5);
        $parameter_saver->save(5, -1);
    }

    public function itDealsWithDatabaseError()
    {
        $dao = mock('Tuleap\SvnCore\Cache\ParameterDao');
        stub($dao)->save()->returns(false);

        $event_manager = \Mockery::mock(\EventManager::class);
        $event_manager->shouldNotReceive('processEvent');

        $this->expectException('Tuleap\SvnCore\Cache\ParameterDataAccessException');

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(5, 5);
    }
}
