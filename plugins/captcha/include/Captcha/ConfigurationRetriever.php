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

class ConfigurationRetriever
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
     * @return Configuration
     * @throws \Tuleap\Captcha\ConfigurationNotFoundException
     */
    public function retrieve()
    {
        $row = $this->dao->getConfiguration();

        if ($row === false) {
            throw new ConfigurationNotFoundException();
        }

        return new Configuration($row['site_key'], $row['secret_key']);
    }
}
