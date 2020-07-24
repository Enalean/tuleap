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

namespace Tuleap\SvnCore\Cache;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TestHelper;

class ParameterRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsDefaultParametersIfParameterDoesNotExist(): void
    {
        $dao = \Mockery::spy(\Tuleap\SvnCore\Cache\ParameterDao::class);
        $dao->shouldReceive('search')->andReturns(TestHelper::emptyDar());

        $parameter_manager = new ParameterRetriever($dao);

        $parameter = $parameter_manager->getParameters();

        $this->assertEquals(ParameterRetriever::MAXIMUM_CREDENTIALS_DEFAULT, $parameter->getMaximumCredentials());
        $this->assertEquals(ParameterRetriever::LIFETIME_DEFAULT, $parameter->getLifetime());
    }

    public function testItUsesDatabaseInformationsToCreateParameters(): void
    {
        $parameters_data = TestHelper::arrayToDar(
            [
                'name'  => ParameterRetriever::MAXIMUM_CREDENTIALS,
                'value' => 877
            ],
            [
                'name' => ParameterRetriever::LIFETIME,
                'value' => 947
            ]
        );
        $dao             = \Mockery::spy(\Tuleap\SvnCore\Cache\ParameterDao::class);
        $dao->shouldReceive('search')->andReturns($parameters_data);

        $parameter_manager = new ParameterRetriever($dao);

        $parameter = $parameter_manager->getParameters();

        $this->assertEquals(877, $parameter->getMaximumCredentials());
        $this->assertEquals(947, $parameter->getLifetime());
    }

    public function testItThrowsAnExceptionIfDatabaseCanNotBeQueried(): void
    {
        $dao = \Mockery::spy(\Tuleap\SvnCore\Cache\ParameterDao::class);
        $dao->shouldReceive('search')->andReturns(false);

        $parameter_manager = new ParameterRetriever($dao);

        $this->expectException(\Tuleap\SvnCore\Cache\ParameterDataAccessException::class);
        $parameter_manager->getParameters();
    }
}
