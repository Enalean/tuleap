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

declare(strict_types=1);

namespace Tuleap\REST;

use PDO;

/**
 * This class exists as a proxy to manipulate the internal state of Tuleap configuration
 *
 * In the future, it should be replaced by a REST call to an API to do this kind of configuration
 */
final class TuleapConfig
{
    public const string FORGE_ACCESS = 'access_mode';
    public const string ANONYMOUS    = 'anonymous';
    public const string REGULAR      = 'regular';
    public const string RESTRICTED   = 'restricted';

    private PDO $dbh;

    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->connect();
        }
        return self::$instance;
    }

    private function setConfig(string $name, string $value): void
    {
        $statment = $this->dbh->prepare('REPLACE INTO forgeconfig (name, value) VALUES (:name, :value)');
        $statment->bindParam(':name', $name);
        $statment->bindParam(':value', $value);
        $statment->execute();
    }

    public function getAccess(): string
    {
        $statement = $this->dbh->prepare('SELECT value FROM forgeconfig WHERE name = :name');
        $statement->execute([':name' => self::FORGE_ACCESS]);
        return $statement->fetchAll()[0]['value'];
    }

    public function setForgeToRestricted(): void
    {
        $this->setConfig(self::FORGE_ACCESS, self::RESTRICTED);
    }

    public function setForgeToAnonymous(): void
    {
        $this->setConfig(self::FORGE_ACCESS, self::ANONYMOUS);
    }

    public function setForgeToRegular(): void
    {
        $this->setConfig(self::FORGE_ACCESS, self::REGULAR);
    }

    public function disableProjectCreation(): void
    {
        $this->setConfig('sys_use_project_registration', '0');
    }

    public function enableProjectCreation(): void
    {
        $this->setConfig('sys_use_project_registration', '1');
    }

    public function enableInviteBuddies(): void
    {
        $this->setConfig('max_invitations_by_day', '10');
    }

    public function disableInviteBuddies(): void
    {
        $this->setConfig('max_invitations_by_day', '0');
    }

    private function connect(): void
    {
        /** @psalm-suppress MissingFile */
        include_once '/etc/tuleap/conf/database.inc';
        /** @psalm-suppress UndefinedVariable */
        $this->dbh = new PDO("mysql:host=$sys_dbhost;dbname=$sys_dbname", $sys_dbuser, $sys_dbpasswd);
    }
}
