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

class CreateTestUser
{
    private $email;

    private $realname;
    private $login;

    /**
     *
     * @param $firstname
     * @param $lastname
     * @param $email
     * @param $login
     * @throws Exception\InvalidLoginException
     * @throws Exception\InvalidRealNameException
     */
    public function __construct($firstname, $lastname, $email, $login)
    {
        $this->email = trim($email);

        $realname = trim($firstname) . ' ' . trim($lastname);
        $valid = new \Valid_RealNameFormat();
        if (! $valid->validate($realname)) {
            throw new Exception\InvalidRealNameException("Invalid realname $realname");
        }
        $this->realname = $realname;

        $login = trim($login);
        $valid = new \Valid_UserNameFormat();
        if (! $valid->validate($login)) {
            throw new Exception\InvalidLoginException("Submitted login is not valid");
        }
        $this->login = $login;
    }

    /**
     * @return \SimpleXMLElement
     * @throws Exception\EmailNotUniqueException
     * @throws Exception\InvalidRealNameException
     * @throws Exception\InvalidLoginException
     */
    public function generateXML()
    {
        $this->assertEmailUnique();
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><users />');
        $user = $xml->addChild('user');
        $user->addChild('id', 101);
        $this->addCData($user, 'username', $this->getUserName());
        $this->addCData($user, 'realname', $this->getRealName());
        $this->addCData($user, 'email', $this->email);
        $user->addChild('ldapid', 101);
        return $xml;
    }

    private function addCData(\SimpleXMLElement $node, $name, $value)
    {
        $parent = $node->addChild($name);
        $dom = dom_import_simplexml($parent);
        if ($dom->ownerDocument === null) {
            return;
        }
        $dom->appendChild($dom->ownerDocument->createCDATASection($value));
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realname;
    }

    /**
     * @throws Exception\EmailNotUniqueException
     */
    private function assertEmailUnique()
    {
        $user_dao = new \UserDao();
        if (count($user_dao->searchByEmail($this->email)) != 0) {
            throw new Exception\EmailNotUniqueException("Email already exists, cannot re-create account.");
        }
    }
}
