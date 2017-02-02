<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class ConfigurationSaverTest extends \TuleapTestCase
{
    public function itSavesKeys()
    {
        $dao = mock('\\Tuleap\\Captcha\\DataAccessObject');
        stub($dao)->save()->returns(true);

        $saver = new ConfigurationSaver($dao);

        $dao->expectOnce('save', array('valid_site_key', 'valid_secret_key'));
        $saver->save('valid_site_key', 'valid_secret_key');
    }

    public function itRejectsInvalidKeys()
    {
        $dao = mock('\\Tuleap\\Captcha\\DataAccessObject');

        $saver = new ConfigurationSaver($dao);

        $this->expectException('\\Tuleap\\Captcha\\ConfigurationMalformedDataException');
        $saver->save(false, false);
    }

    public function itDoesNotSilentUnsuccessfulSave()
    {
        $dao = mock('\\Tuleap\\Captcha\\DataAccessObject');
        stub($dao)->save()->returns(false);

        $saver = new ConfigurationSaver($dao);

        $dao->expectOnce('save', array('valid_site_key', 'valid_secret_key'));
        $this->expectException('\\Tuleap\\Captcha\\ConfigurationDataAccessException');
        $saver->save('valid_site_key', 'valid_secret_key');
    }
}
