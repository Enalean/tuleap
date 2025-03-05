<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

require_once __DIR__ . '/bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ConfigurationSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testKeysAreSaved(): void
    {
        $dao = $this->createMock(DataAccessObject::class);
        $dao->expects(self::once())->method('save')->with('valid_site_key', 'valid_secret_key')->willReturn(true);

        $saver = new ConfigurationSaver($dao);

        $saver->save('valid_site_key', 'valid_secret_key');
    }

    public function testInvalidKeysAreRejected(): void
    {
        $dao = $this->createMock(DataAccessObject::class);

        $saver = new ConfigurationSaver($dao);

        $this->expectException(ConfigurationMalformedDataException::class);

        $saver->save(false, false);
    }

    public function testUnsuccessfulSaveIsNoSilent(): void
    {
        $dao = $this->createMock(DataAccessObject::class);
        $dao->expects(self::once())->method('save')->willReturn(false);

        $saver = new ConfigurationSaver($dao);

        $this->expectException(ConfigurationDataAccessException::class);

        $saver->save('valid_site_key', 'valid_secret_key');
    }
}
