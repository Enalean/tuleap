<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

/**
 * Implementation design:
 *
 * User attributes:
 * - dn:  tuleap login + people dn
 * - uid: tuleap login
 * - sn:  should really be user family name but we don't have this information so
 *        it's the Real Name.
 * - employeeNumber: tuleap unique ID (integer, will never change).
 *                   this corresponds to EdUid
 *
 * Management of user status:
 * - Active and Restricted users are stored the same way.
 *   If we ever need to manage that, it will be in the groups
 * - Suspended users are regular users but with fake password so
 *   they cannot login.
 * - Deleted users are removed from LDAP (but they can be re-activated later)
 */
class LDAP_UserWrite
{

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

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
    /**
     * @var LDAP_UserDao
     */
    private $ldap_user_dao;

    public function __construct(LDAP $ldap, UserManager $user_manager, UserDao $dao, LDAP_UserDao $ldap_user_dao, \Psr\Log\LoggerInterface $logger)
    {
        $this->ldap          = $ldap;
        $this->user_manager  = $user_manager;
        $this->user_dao      = $dao;
        $this->ldap_user_dao = $ldap_user_dao;
        $this->logger        = new WrapperLogger($logger, 'UserWrite');
    }

    public function updateWithPreviousUser(PFUser $old_user, PFUser $new_user)
    {
        try {
            if ($this->userIsFirstAdmin($new_user)) {
                return;
            }
            $this->rename($old_user, $new_user);
            $this->updateWithUser($new_user);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    public function updateWithUserId($user_id)
    {
        $user = $this->user_manager->getUserById($user_id);
        if ($user && $user->isAlive()) {
            $this->updateWithUser($user);
        } else {
            $this->logger->warning('Do not write LDAP info about non existant or suspended users ' . $user_id);
        }
    }

    public function updateWithUser(PFUser $user)
    {
        try {
            $this->update($user);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function update(PFUser $user)
    {
        if ($this->userIsFirstAdmin($user)) {
            return;
        }
        $dn = $this->getUserDN($user);
        if ($this->entryExists($user)) {
            if ($user->isAlive()) {
                $this->ldap->update($dn, $this->getLDAPInfo($user));
            } elseif ($user->isSuspended()) {
                $info = $this->getLDAPInfo($user);
                $info['userPassword'] = '!' . $this->getLDAPPassword($user);
                $this->ldap->update($dn, $info);
            } else {
                $this->ldap->delete($dn);
            }
        } else {
            $this->create($user);
        }
    }

    private function userIsFirstAdmin(PFUser $user)
    {
        return $user->getId() == 101;
    }

    private function create(PFUser $user)
    {
        if ($user->getPassword() != '') {
            $this->ldap->add($this->getUserDN($user), $this->getLDAPInfo($user));
            $this->updateUserLdapId($user);
            $this->ldap_user_dao->createLdapUser($user->getId(), $_SERVER['REQUEST_TIME'], $this->getUserLdapId($user));
        } else {
            $this->logger->debug('No password for user ' . $user->getUnixName() . ' ' . $user->getId() . ' skip LDAP account creation');
        }
    }

    private function getLDAPPassword(PFUser $user)
    {
        $ldap_result_iterator = $this->ldap->searchDn($this->getUserDN($user), array('userPassword'));
        if ($ldap_result_iterator !== false && count($ldap_result_iterator) === 1) {
            $ldap_result = $ldap_result_iterator->current();
            if (count($ldap_result)) {
                return base64_decode($ldap_result->get('userPassword'));
            }
        }
        return '';
    }

    private function entryExists(PFUser $user)
    {
        $ldap_result_iterator = $this->ldap->searchDn($this->getUserDN($user), array('dn'));
        if ($ldap_result_iterator !== false && count($ldap_result_iterator) == 1) {
            return true;
        }
        return false;
    }

    private function getUserDN(PFUser $user)
    {
        return $this->getUserRDN($user) . ',' . $this->ldap->getLDAPParam('write_people_dn');
    }

    private function getUserRDN(PFUser $user)
    {
        return 'uid=' . $this->getUserLdapId($user);
    }

    private function updateUserLdapId(PFUser $user)
    {
        $user->setLdapId($user->getUserName());
        $this->user_dao->updateByRow(array('user_id' => $user->getId(), 'ldap_id' => $this->getEdUid($user)));
    }

    private function getLDAPInfo(PFUser $user)
    {
        $info = array(
            "employeeNumber" => $this->getEdUid($user),
            "cn"             => $user->getRealName(),
            "sn"             => $user->getRealName(),
            "displayName"    => $user->getRealName(),
            "mail"           => $user->getEmail(),
            "uid"            => $this->getUserLdapId($user),
            'gidNumber'      => $user->getSystemUnixGid(),
            'uidNumber'      => $user->getSystemUnixUid(),
            'homeDirectory'  => $user->getUnixHomeDir(),
            "objectclass"    => array(
                "posixAccount",
                "inetOrgPerson",
            )
        );
        if ($user->getPassword() != '') {
            $info['userPassword'] = $this->getEncryptedPassword($user->getPassword());
        }
        return $info;
    }

    private function getUserLdapId(PFUser $user)
    {
        return strtolower($user->getUserName());
    }

    private function getEdUid(PFUser $user)
    {
        return $user->getId();
    }

    private function getEncryptedPassword($password)
    {
        return '{CRYPT}' . crypt($password, '$6$rounds=50000$' . bin2hex(openssl_random_pseudo_bytes(25) . '$'));
    }

    private function rename(PFUser $old_user, PFUser $new_user)
    {
        $new_dn = $this->getUserDN($new_user);
        $old_dn = $this->getUserDN($old_user);
        if ($new_dn != $old_dn) {
            $this->ldap->renameUser($old_dn, $this->getUserRDN($new_user));
            $this->updateUserLdapId($new_user);
            $this->ldap_user_dao->updateLdapUid($new_user->getId(), $new_user->getLdapId());
        }
    }
}
