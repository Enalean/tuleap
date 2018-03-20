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
    private $firstname;
    private $lastname;
    private $email;

    private $realname;
    private $login;

    public function __construct($firstname, $lastname, $email)
    {
        $this->firstname = trim($firstname);
        $this->lastname  = trim($lastname);
        $this->email     = trim($email);
    }

    /**
     * @return \SimpleXMLElement
     * @throws Exception\EmailNotUniqueException
     * @throws Exception\InvalidRealNameException
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
        $dom->appendChild($dom->ownerDocument->createCDATASection($value));
    }

    public function getUserName()
    {
        if ($this->login === null) {
            $this->login = $this->generateLogin($this->firstname.'_'.$this->lastname);
        }
        return $this->login;
    }

    private function generateLogin($uid)
    {
        $account_name = $this->getLoginFromString($uid);
        $uid = $account_name;
        $i=2;
        $valid = new \Valid_UserNameFormat();
        while (! $valid->validate($uid)) {
            $uid = $account_name.$i;
            $i++;
        }
        return $uid;
    }

    public function getLoginFromString($string)
    {
        $string = str_replace([' ', '_'], '-', $string);
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        $string = trim($string, '-');
        $string = substr($string, 0, 28);
        return strtolower($string);
    }

    /**
     * @return string
     * @throws Exception\InvalidRealNameException
     */
    public function getRealName()
    {
        if ($this->realname === null) {
            $realname = $this->firstname.' '.$this->lastname;
            $valid = new \Valid_RealNameFormat();
            if (! $valid->validate($realname)) {
                throw new Exception\InvalidRealNameException("Invalid realname $realname");
            }
            $this->realname = $realname;
        }
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
