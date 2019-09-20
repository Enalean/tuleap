<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Handle synchro between LDAP and Tuleap user.
 */
class LDAP_UserSync_Orange extends LDAP_UserSync
{

    /**
     * Return the sync attributes
     *
     * @return array
     */
    public function getSyncAttributes($ldap)
    {
        //Define the default sync attributes
        $this->attributes = array($ldap->getLDAPParam('cn'), $ldap->getLDAPParam('mail'), $ldap->getLDAPParam('uid'));
        return $this->attributes;
    }

    /**
     * Do all the synchronization between an ldap result and a Tuleap user.
     *
     * This method returns if it modified the user or not. This is usefull during
     * batch process in order to limit computing.
     *
     * @param PFUser       $user User
     * @param LDAPResult $lr   Ldap result
     *
     * @return bool True if the method modified the user object
     */
    public function sync(PFUser $user, LDAPResult $lr)
    {
        $modified  = false;

        $ldapEmail = $lr->getEmail();
        $realname  = ucwords(preg_replace('/^(\w+).(\w+)@.*/', '\\1 \\2', $ldapEmail));

        if (($realname !== null) && ($user->getRealName() != substr($realname, 0, 32))) {
            $user->setRealName($realname);
            $modified = true;
        }

        if (($ldapEmail !== null) && ($user->getEmail() != $ldapEmail)) {
            $user->setEmail($ldapEmail);
            $modified = true;
        }

        return $modified;
    }
}
