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

use DataAccessException;

class ParameterRetriever
{
    const MAXIMUM_CREDENTIALS         = 'maximum_credentials';
    const LIFETIME                    = 'lifetime';
    const MAXIMUM_CREDENTIALS_DEFAULT = 10;
    const LIFETIME_DEFAULT            = 5;

    /**
     * @var ParameterDao
     */
    private $dao;

    public function __construct(ParameterDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return Parameters
     * @throws DataAccessException
     */
    public function getParameters()
    {
        $rows = $this->dao->search();
        if ($rows === false) {
            throw new DataAccessException();
        }

        $maximum_credentials = self::MAXIMUM_CREDENTIALS_DEFAULT;
        $lifetime            = self::LIFETIME_DEFAULT;

        foreach ($rows as $row) {
            switch ($row['name']) {
                case self::MAXIMUM_CREDENTIALS:
                    $maximum_credentials = $row['value'];
                    break;
                case self::LIFETIME:
                    $lifetime = $row['value'];
                    break;
            }
        }

        return new Parameters($maximum_credentials, $lifetime);
    }
}
