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

use Valid_String;

class ConfigurationSaver
{
    /**
     * @var DataAccessObject
     */
    private $dao;

    public function __construct(DataAccessObject $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @throws ConfigurationMalformedDataException
     * @throws ConfigurationDataAccessException
     */
    public function save($site_key, $secret_key)
    {
        if (! $this->isConfigurationValid($site_key, $secret_key)) {
            throw new ConfigurationMalformedDataException();
        }

        $is_saved = $this->dao->save($site_key, $secret_key);

        if (! $is_saved) {
            throw new ConfigurationDataAccessException();
        }
    }

    /**
     * @return bool
     */
    private function isConfigurationValid($site_key, $secret_key)
    {
        $string_validator = new Valid_String();
        $string_validator->required();

        return $string_validator->validate($site_key) && $string_validator->validate($secret_key);
    }
}
