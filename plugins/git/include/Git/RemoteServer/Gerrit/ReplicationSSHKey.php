<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Git_RemoteServer_Gerrit_ReplicationSSHKey implements IHaveAnSSHKey //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public const string KEYNAME_PREFIX = 'gerrit_';
    public const string KEYNAME_SUFFIX = '@0.pub';

    /** @var string */
    private $value = null;

    /** @var int */
    private $host_id = null;

    /**
     * @param string $value
     * @return \Git_RemoteServer_Gerrit_ReplicationSSHKey
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param int $id
     * @return \Git_RemoteServer_GerritReplicationSSHKey
     */
    public function setGerritHostId($id)
    {
        $this->host_id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getGerritHostId()
    {
        return $this->host_id;
    }

    /**
     * SSH key of replication
     * @return array
     */
    #[\Override]
    public function getAuthorizedKeysArray()
    {
        if ($this->value) {
            return [$this->value];
        }
        return [];
    }

    /**
     * Fake username for replication
     */
    #[\Override]
    public function getUserName(): string
    {
        return Rule_UserName::RESERVED_PREFIX . self::KEYNAME_PREFIX . $this->getGerritHostId();
    }

    /**
     * File name of gitolite key
     *
     * @return String
     */
    public function getGitoliteKeyFile()
    {
        return $this->getUserName() . self::KEYNAME_SUFFIX;
    }
}
