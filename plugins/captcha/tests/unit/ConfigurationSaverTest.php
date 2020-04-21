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

class ConfigurationSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testKeysAreSaved()
    {
        $dao = \Mockery::mock(DataAccessObject::class);
        $dao->shouldReceive('save')->with('valid_site_key', 'valid_secret_key')->once()->andReturns(true);

        $saver = new ConfigurationSaver($dao);

        $saver->save('valid_site_key', 'valid_secret_key');
    }

    public function testInvalidKeysAreRejected()
    {
        $dao = \Mockery::mock(DataAccessObject::class);

        $saver = new ConfigurationSaver($dao);

        $this->expectException(ConfigurationMalformedDataException::class);

        $saver->save(false, false);
    }

    public function testUnsuccessfulSaveIsNoSilent()
    {
        $dao = \Mockery::mock(DataAccessObject::class);
        $dao->shouldReceive('save')->andReturns(false)->once();

        $saver = new ConfigurationSaver($dao);

        $this->expectException(ConfigurationDataAccessException::class);

        $saver->save('valid_site_key', 'valid_secret_key');
    }
}
