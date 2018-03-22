<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\CreateTestEnv;

use Tuleap\DB\DataAccessObject;

class NotificationBotDao extends DataAccessObject
{
    public function get()
    {
        return $this->getDB()->single('SELECT bot_id FROM plugin_create_test_env_bot');
    }

    public function remove()
    {
        $this->getDB()->run('TRUNCATE plugin_create_test_env_bot');
    }

    /**
     * @param int $bot_id
     */
    public function save($bot_id)
    {
        $this->getDB()->beginTransaction();

        $this->remove();
        $this->insert($bot_id);

        $this->getDB()->commit();
    }

    /**
     * @param int $bot_id
     */
    private function insert($bot_id)
    {
        $sql = 'INSERT INTO plugin_create_test_env_bot(bot_id) VALUES (?)';
        $this->getDB()->run($sql, $bot_id);
    }
}
