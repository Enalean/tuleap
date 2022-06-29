<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

/**
 *  Data Access Object for Plugin
 */
class PluginDao extends DataAccessObject
{
    public const ENABLED_COLUMN = 'available';

    /**
    * Gets all tables of the db
    * @return LegacyDataAccessResultInterface
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM plugin";
        return $this->retrieve($sql);
    }

    /**
    * Searches Plugin by Id
    * @return LegacyDataAccessResultInterface
    */
    public function searchById($id)
    {
        $sql = sprintf(
            "SELECT * FROM plugin WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->retrieve($sql);
    }

    /**
    * Searches Plugin by Name
    * @return LegacyDataAccessResultInterface
    */
    public function searchByName($name)
    {
        $sql = sprintf(
            "SELECT * FROM plugin WHERE name = %s",
            $this->da->quoteSmart($name)
        );
        return $this->retrieve($sql);
    }

    /**
    * @return LegacyDataAccessResultInterface|false
    */
    public function searchEnabledPlugins(): mixed
    {
        $sql = "SELECT *
                FROM plugin
                WHERE available = 1
                ORDER BY id";

        return $this->retrieve($sql);
    }


    /**
    * @return int|false
    */
    public function create(string $name): mixed
    {
        $sql = sprintf(
            "INSERT INTO plugin (name, available) VALUES (%s, 0);",
            $this->da->quoteSmart($name)
        );
        return $this->updateAndGetLastId($sql);
    }

    public function enablePlugin(int $id): void
    {
        $sql = sprintf(
            "UPDATE plugin
                    SET available = 1
                    WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        $this->update($sql);
    }

    public function disablePlugin(int $id): void
    {
        $sql = sprintf(
            "UPDATE plugin
                    SET available = 0
                    WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        $this->update($sql);
    }

    public function removeById($id)
    {
        $sql = sprintf(
            "DELETE FROM plugin WHERE id = %s",
            $this->da->quoteSmart($id)
        );
        return $this->update($sql);
    }

    public function restrictProjectPluginUse($pluginId, $restrict)
    {
        $_usage = ($restrict === true ? 1 : 0);
        $sql    = sprintf(
            'UPDATE plugin' .
                       ' SET prj_restricted = %d' .
                       ' WHERE id = %d',
            $_usage,
            $pluginId
        );

        /** @psalm-suppress DeprecatedMethod */
        return $this->update($sql);
    }

    public function searchProjectPluginRestrictionStatus($pluginId)
    {
        $sql = sprintf(
            'SELECT prj_restricted' .
                       ' FROM plugin' .
                       ' WHERE id = %d',
            $pluginId
        );
        return $this->retrieve($sql);
    }
}
