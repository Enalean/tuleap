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

class DataAccessObject extends \Tuleap\DB\DataAccessObject
{
    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->getDB()->row('SELECT * FROM plugin_captcha_configuration');
    }

    /**
     * @return bool
     */
    public function save($site_key, $secret_key)
    {
        $this->getDB()->beginTransaction();

        try {
            $this->getDB()->run('DELETE FROM plugin_captcha_configuration');
            $this->getDB()->run(
                'INSERT INTO plugin_captcha_configuration(site_key, secret_key) VALUES (?, ?)',
                $site_key,
                $secret_key
            );
        } catch (\PDOException $ex) {
            $this->getDB()->rollBack();
            return false;
        }

        return $this->getDB()->commit();
    }
}
