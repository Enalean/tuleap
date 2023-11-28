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

namespace Tuleap\SVNCore\Cache;

final class ParameterSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItSavesParameters(): void
    {
        $dao = $this->createMock(\Tuleap\SVNCore\Cache\ParameterDao::class);
        $dao->expects(self::once())->method('save')->willReturn(true);

        $event_manager = $this->createMock(\EventManager::class);
        $event_manager->method('processEvent');

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(5, 5);
    }

    public function testItRejectsInvalidData(): void
    {
        $dao = $this->createMock(\Tuleap\SVNCore\Cache\ParameterDao::class);
        $dao->expects(self::never())->method('save');

        $event_manager = $this->createMock(\EventManager::class);
        $event_manager->expects(self::never())->method('processEvent');

        $this->expectException(\Tuleap\SVNCore\Cache\ParameterMalformedDataException::class);

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(-1);
        $parameter_saver->save(5);
    }

    public function testItDealsWithDatabaseError(): void
    {
        $dao = $this->createMock(\Tuleap\SVNCore\Cache\ParameterDao::class);
        $dao->method('save')->willReturn(false);

        $event_manager = $this->createMock(\EventManager::class);
        $event_manager->expects(self::never())->method('processEvent');

        $this->expectException(\Tuleap\SVNCore\Cache\ParameterDataAccessException::class);

        $parameter_saver = new ParameterSaver($dao, $event_manager);
        $parameter_saver->save(5);
    }
}
