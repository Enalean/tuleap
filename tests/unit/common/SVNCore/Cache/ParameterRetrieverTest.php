<?php
/**
 * Copyright (c) Enalean, 2016- Present. All Rights Reserved.
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

use TestHelper;

final class ParameterRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsDefaultParametersIfParameterDoesNotExist(): void
    {
        $dao = $this->createMock(\Tuleap\SVNCore\Cache\ParameterDao::class);
        $dao->method('search')->willReturn(TestHelper::emptyDar());

        $parameter_manager = new ParameterRetriever($dao);

        $parameter = $parameter_manager->getParameters();

        self::assertEquals(ParameterRetriever::LIFETIME_DEFAULT, $parameter->getLifetime());
    }

    public function testItUsesDatabaseInformationsToCreateParameters(): void
    {
        $parameters_data = TestHelper::arrayToDar(
            [
                'name' => ParameterRetriever::LIFETIME,
                'value' => 947,
            ]
        );
        $dao             = $this->createMock(\Tuleap\SVNCore\Cache\ParameterDao::class);
        $dao->method('search')->willReturn($parameters_data);

        $parameter_manager = new ParameterRetriever($dao);

        $parameter = $parameter_manager->getParameters();

        self::assertEquals(947, $parameter->getLifetime());
    }

    public function testItThrowsAnExceptionIfDatabaseCanNotBeQueried(): void
    {
        $dao = $this->createMock(\Tuleap\SVNCore\Cache\ParameterDao::class);
        $dao->method('search')->willReturn(false);

        $parameter_manager = new ParameterRetriever($dao);

        $this->expectException(\Tuleap\SVNCore\Cache\ParameterDataAccessException::class);
        $parameter_manager->getParameters();
    }
}
