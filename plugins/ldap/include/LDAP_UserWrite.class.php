<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class LDAP_UserWrite {

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var LDAP
     */
    private $ldap;

    /**
     * @var UserDao
     */
    private $user_dao;

    public function __construct(LDAP $ldap, UserManager $user_manager, UserDao $dao) {
        $this->ldap         = $ldap;
        $this->user_manager = $user_manager;
        $this->user_dao     = $dao;
    }

    public function addByUserName($user_name, $password) {
        $user = $this->user_manager->getUserByUserName($user_name, $password);
        if ($user) {
            $user->setPassword($password);
            $this->update($user);
        }
    }

    public function update(PFUser $user) {
        $dn   = 'uid='.$user->getUserName().','.$this->ldap->getLDAPParam('write_people_dn');
        $info = $this->getLDAPInfo($user);

        if ($user->getLdapId() == '') {
            $this->ldap->add($dn, $info);
            $user->setLdapId($user->getUserName());
            $this->user_dao->updateByRow(array('user_id' => $user->getId(), 'ldap_id' => $user->getUserName()));
        } else {
            $this->ldap->update($dn, $info);
        }
    }

    private function getLDAPInfo(PFUser $user) {
        $info = array(
            "cn"            => $user->getRealName(),
            "sn"            => $user->getRealName(),
            "displayName"   => $user->getRealName(),
            "mail"          => $user->getEmail(),
            "uid"           => $user->getUserName(),
            'gidNumber'     => $user->getSystemUnixGid(),
            'uidNumber'     => $user->getSystemUnixUid(),
            'homeDirectory' => $user->getUnixHomeDir(),
            "objectclass"   => array(
                "posixAccount",
                "inetOrgPerson",
            )
        );
        if ($user->getPassword() != '') {
            $info['userPassword'] = $this->getEncryptedPassword($user->getPassword());
        }
        return $info;
    }

    private function getEncryptedPassword($password) {
        return '{CRYPT}'.crypt($password, '$6$rounds=50000$' . bin2hex(openssl_random_pseudo_bytes(25) . '$'));
    }
}
