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

namespace Tuleap\Git\Gitolite\SSHKey;

class Key
{
    /**
     * @var \Rule_UserName
     */
    private static $username_rule;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $key;

    /**
     * @throws \InvalidArgumentException
     */
    public function __construct($username, $key)
    {
        if (! $this->isUsernameValid($username)) {
            throw new \InvalidArgumentException('The username must be UNIX valid');
        }
        $this->username = $username;
        $this->key      = trim($key);
        if (mb_strpos($this->key, "\n") !== false) {
            throw new \InvalidArgumentException('Only one key is expected');
        }
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return bool
     */
    private function isUsernameValid($username)
    {
        if (self::$username_rule === null) {
            self::$username_rule = new \Rule_UserName();
        }
        return self::$username_rule->isUnixValid($username);
    }
}
