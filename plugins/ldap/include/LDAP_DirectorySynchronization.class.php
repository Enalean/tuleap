<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2009.
 * 
 * This file is a part of Codendi.
 * 
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'LDAP.class.php';
require_once 'LDAP_UserManager.class.php';

class LDAP_DirectorySynchronization {
    /**
     * @var LDAP
     */
    protected $ldap;
    protected $ldapTime;
    protected $sync;
    protected $lum;
    protected $um;

    public function __construct(LDAP $ldap) {
        $this->ldapTime = 0;
        $this->ldap     = $ldap;
    }

    public function syncAll() {
        $sql = 'SELECT u.user_id, user_name, email, ldap_id, status, realname, ldap_uid
        FROM user u
         JOIN plugin_ldap_user ldap_user ON (ldap_user.user_id = u.user_id)
        WHERE status IN ("A", "R")
        AND u.user_id > 101
        AND ldap_id IS NOT NULL
        AND ldap_id <> ""';

        $res = db_query($sql);
        if ($res && !db_error($res)) {
            while($row = db_fetch_array($res)) {
                $this->ldapSync($row);
            }
            $this->getLdapUserManager()->triggerRenameOfUsers();
        } else {
            echo "DB error: ".db_error($res).PHP_EOL;
        }
    }

    public function ldapSync($row) {
        $ldap_query = $this->ldap->getLDAPParam('eduid').'='.$row['ldap_id'];

        $userSync = $this->getLdapUserSync();
        $attributes = $userSync->getSyncAttributes($this->ldap);

        $time_start = microtime(true);
        $lri = false;
        foreach (split(';', $this->ldap->getLDAPParam('people_dn')) as $PeopleDn) {
            $lri = $this->ldap->search($PeopleDn, $ldap_query, LDAP::SCOPE_ONELEVEL, $attributes);
            if (count($lri) == 1 && $lri != false) {
                break;
            }
        }
        $time_end   = microtime(true);
        $this->ldapTime += ($time_end-$time_start);

        if ($this->ldap->getErrno() === LDAP::ERR_SUCCESS && $lri) {
            $user     = new PFUser($row);
            $modified = false;

            if (count($lri) == 1) {
                $lr       = $lri->current();
                $modified = $userSync->sync($user, $lr);

                if ($row['ldap_uid'] != $lr->getLogin()) {
                    $this->getLdapUserManager()->updateLdapUid($user, $lr->getLogin());
                }
            } elseif (count($lri) == 0) {
                // User not found in LDAP directory
                $modified = true;
                $user->setStatus('S');
                $user->setUnixStatus('D');
            }

            if ($modified) {
                $this->getUserManager()->updateDb($user);
            }
        }
    }

    public function getElapsedLdapTime() {
        return $this->ldapTime;
    }

    protected function getUserManager() {
        return UserManager::instance();
    }

    protected function getLdapUserManager() {
        if (!isset($this->lum)) {
            $this->lum = new LDAP_UserManager($this->ldap);
        }
        return $this->lum;
    }

    protected function getLdapUserSync() {
        return LDAP_UserSync::instance();
    }
}
?>