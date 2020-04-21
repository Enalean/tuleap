<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Captcha;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class ConfigurationRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testConfigurationIsRetrieved()
    {
        $dao = \Mockery::mock(DataAccessObject::class);
        $dao->shouldReceive('getConfiguration')->andReturns([
            'site_key'   => 'site_key',
            'secret_key' => 'secret_key'
        ]);

        $configuration_retriever = new ConfigurationRetriever($dao);
        $configuration           = $configuration_retriever->retrieve();

        $this->assertSame($configuration->getSiteKey(), 'site_key');
        $this->assertSame($configuration->getSecretKey(), 'secret_key');
    }

    public function testAnExceptionIsThrownWhenConfigurationIsNotFound()
    {
        $dao = \Mockery::mock(DataAccessObject::class);
        $dao->shouldReceive('getConfiguration')->andReturns(false);

        $configuration_retriever = new ConfigurationRetriever($dao);

        $this->expectException(ConfigurationNotFoundException::class);
        $configuration_retriever->retrieve();
    }
}
