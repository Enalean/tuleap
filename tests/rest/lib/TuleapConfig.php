<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Test\Rest;

use PDO;

class TuleapConfig
{
    public const FORGE_ACCESS = 'access_mode';
    public const ANONYMOUS    = 'anonymous';
    public const REGULAR      = 'regular';
    public const RESTRICTED   = 'restricted';

    /**
     * @var PDO
     */
    private $dbh;

    private static $instance;

    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new TuleapConfig();
            self::$instance->connect();
        }
        return self::$instance;
    }

    private function setConfig($name, $value)
    {
        $statment = $this->dbh->prepare('REPLACE INTO forgeconfig (name, value) VALUES (:name, :value)');
        $statment->bindParam(':name', $name);
        $statment->bindParam(':value', $value);
        $statment->execute();
    }

    public function setForgeToRestricted()
    {
        $this->setConfig(self::FORGE_ACCESS, self::RESTRICTED);
    }

    public function setForgeToAnonymous()
    {
        $this->setConfig(self::FORGE_ACCESS, self::ANONYMOUS);
    }

    public function setForgeToRegular()
    {
        $this->setConfig(self::FORGE_ACCESS, self::REGULAR);
    }

    public function enableInviteBuddies(): void
    {
        $this->setConfig('max_invitations_by_day', "10");
    }

    public function disableInviteBuddies(): void
    {
        $this->setConfig('max_invitations_by_day', "0");
    }

    private function connect()
    {
        include_once '/etc/tuleap/conf/database.inc';
        $this->dbh = new PDO("mysql:host=$sys_dbhost;dbname=$sys_dbname", $sys_dbuser, $sys_dbpasswd);
    }
}
