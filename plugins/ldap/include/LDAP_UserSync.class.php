<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
    /**
     * @var self
     */
    private static $instance;
    protected $attributes;

    /**
     * Constructor
     */
    public function __construct(private readonly ?\Psr\Log\LoggerInterface $logger = null)
    {
    }

    /**
     * Instanciate the right LDAP_UserSync object
     *
     * Site can define its own implementation in /etc/tuleap/plugins/ldap/site-content/en_US/synchronize_user.txt
     *
     * @return LDAP_UserSync
     */
    public static function instance()
    {
        if (! isset(self::$instance)) {
            $syncClass = self::class;
            // Allows site defined user update
            include_once($GLOBALS['Language']->getContent('synchronize_user', 'en_US', 'ldap'));
            if ($syncClass === self::class) {
                self::$instance = new $syncClass(new \Tuleap\LDAP\LdapLogger());
            } else {
                self::$instance = new $syncClass();
            }
        }
        return self::$instance;
    }

    /**
     * Return the sync attributes
     *
     * @param LDAP $ldap
     *
     * @return array
     */
    public function getSyncAttributes($ldap)
    {
        //Define the default sync attributes
        $this->attributes = [$ldap->getLDAPParam('cn'), $ldap->getLDAPParam('mail'), $ldap->getLDAPParam('uid')];
        return $this->attributes;
    }

    /**
     * Do all the synchronization between an ldap result and a Tuleap user.
     *
     * This method returns if it modified the user or not. This is usefull during
     * batch process in order to limit computing.
     *
     * @param PFUser     $user Tuleap user
     * @param LDAPResult $lr   Ldap result
     *
     * @return bool True if the method modified the user object
     */
    public function sync(PFUser $user, LDAPResult $lr)
    {
        $modified = false;

        if (($lr->getCommonName() !== null) && ($user->getRealName() != $lr->getCommonName())) {
            $this->logger?->info("[LDAP default sync] Not matching Real Name between LDAP and DB for user #" . $user->getId());
            $this->logger?->info("DB Real Name: " . $user->getRealName());
            $this->logger?->info("LDAP Real Name: " . $lr->getCommonName());
            $user->setRealName($this->getCommonName($lr));
            $modified = true;
        }

        if (($lr->getEmail() !== null) && ($user->getEmail() != $lr->getEmail())) {
            $this->logger?->info("[LDAP default sync] Not matching Email between LDAP and DB for user #" . $user->getId());
            $this->logger?->info("DB email: " . $user->getEmail());
            $this->logger?->info("LDAP email: " . $lr->getEmail());
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
