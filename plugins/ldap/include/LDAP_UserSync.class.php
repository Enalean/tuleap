<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2010.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Handle synchro between LDAP and Codendi user.
 */
class LDAP_UserSync
{

    private static $instance;
    protected $attributes;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Instanciate the right LDAP_UserSync object
     *
     * Site can define its own implementation in /etc/codendi/plugins/ldap/site-content/en_US/synchronize_user.txt
     *
     * @return LDAP_UserSync
     */
    public static function instance()
    {
        if (!isset(self::$instance)) {
            $syncClass = self::class;
            // Allows site defined user update
            include_once($GLOBALS['Language']->getContent('synchronize_user', 'en_US', 'ldap'));
            self::$instance = new $syncClass;
        }
        return self::$instance;
    }

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
     * Set the sync attributes
     *
     * @param Array $values
     */
    public function setSyncAttributes($values)
    {
        $this->attributes = $values;
    }

    /**
     * Do all the synchronization between an ldap result and a Codendi user.
     *
     * This method returns if it modified the user or not. This is usefull during
     * batch process in order to limit computing.
     *
     * @param PFUser       $user Codendi user
     * @param LDAPResult $lr   Ldap result
     *
     * @return bool True if the method modified the user object
     */
    public function sync(PFUser $user, LDAPResult $lr)
    {
        $modified = false;

        if (($lr->getCommonName() !== null) && ($user->getRealName() != substr($lr->getCommonName(), 0, 32))) {
            $user->setRealName($this->getCommonName($lr));
            $modified = true;
        }

        if (($lr->getEmail() !== null) && ($user->getEmail() != $lr->getEmail())) {
            $user->setEmail($lr->getEmail());
            $modified = true;
        }

        return $modified;
    }

    public function getCommonName(LDAPResult $lr)
    {
        return $lr->getCommonName();
    }
}
