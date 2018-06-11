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

namespace Tuleap\CallMeBack;

use Tuleap\DB\DataAccessObject;
use PDOException;

class CallMeBackEmailDao extends DataAccessObject
{
    public function get()
    {
        return $this->getDB()->single('SELECT email_to FROM plugin_callmeback_email');
    }

    /**
     * @param string $email
     * @throws PDOException
     */
    public function save($email)
    {
        $this->getDB()->beginTransaction();

        try {
            $this->remove();
            $this->insert($email);
        } catch (PDOException $exception) {
            $this->getDB()->rollBack();
            throw $exception;
        }

        $this->getDB()->commit();
    }

    private function remove()
    {
        $this->getDB()->run('TRUNCATE plugin_callmeback_email');
    }

    /**
     * @param string $email
     */
    private function insert($email)
    {
        $sql = 'INSERT INTO plugin_callmeback_email(email_to) VALUES (?)';
        $this->getDB()->run($sql, $email);
    }
}
