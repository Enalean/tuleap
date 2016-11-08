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

use TestHelper;
use TuleapTestCase;

class ParameterRetrieverTest extends TuleapTestCase
{
    public function itReturnsDefaultParametersIfParameterDoesNotExist()
    {
        $dao = mock('Tuleap\SvnCore\Cache\ParameterDao');
        stub($dao)->search()->returns(TestHelper::emptyDar());

        $parameter_manager = new ParameterRetriever($dao);

        $parameter = $parameter_manager->getParameters();

        $this->assertEqual($parameter->getMaximumCredentials(), ParameterRetriever::MAXIMUM_CREDENTIALS_DEFAULT);
        $this->assertEqual($parameter->getLifetime(), ParameterRetriever::LIFETIME_DEFAULT);
    }

    public function itUsesDatabaseInformationsToCreateParameters()
    {
        $parameters_data = TestHelper::arrayToDar(
            array(
                'name'  => ParameterRetriever::MAXIMUM_CREDENTIALS,
                'value' => 877
            ),
            array(
                'name' => ParameterRetriever::LIFETIME,
                'value' => 947
            )
        );
        $dao             = mock('Tuleap\SvnCore\Cache\ParameterDao');
        stub($dao)->search()->returns($parameters_data);

        $parameter_manager = new ParameterRetriever($dao);

        $parameter = $parameter_manager->getParameters();

        $this->assertEqual($parameter->getMaximumCredentials(), 877);
        $this->assertEqual($parameter->getLifetime(), 947);
    }

    public function itThrowsAnExceptionIfDatabaseCanNotBeQueried()
    {
        $dao = mock('Tuleap\SvnCore\Cache\ParameterDao');
        stub($dao)->search()->returns(false);

        $parameter_manager = new ParameterRetriever($dao);

        $this->expectException('Tuleap\SvnCore\Cache\ParameterDataAccessException');
        $parameter_manager->getParameters();
    }
}
