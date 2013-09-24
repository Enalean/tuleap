<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class OpenId_AccountManager {
    /** @var OpenId_Dao */
    private $dao;

    /** @var type */
    private $user_manager;

    public function __construct(Openid_Dao $dao, UserManager $user_manager) {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
    }

    /**
     * Associate a User and an OpenId URL
     *
     * @param PFUser $user
     * @param type $identity_url
     * @throws OpenId_IdentityUrlUpdateException
     * @throws OpenId_IdentityUrlAlreadyPairedException
     */
    public function pairWithIdentityUrl(PFUser $user, $identity_url) {
        if (! $this->isIdentityUrlAlreadyUsed($identity_url)) {
            if (! $this->dao->addConnexionStringForUserId($identity_url, $user->getId())) {
                throw new OpenId_IdentityUrlUpdateException($GLOBALS['Language']->getText('plugin_openid', 'error_identity_url_update'));
            }
        } else {
            throw new OpenId_IdentityUrlAlreadyPairedException($GLOBALS['Language']->getText('plugin_openid', 'error_already_paired', array(Config::get('sys_name'))));
        }
    }

    private function isIdentityUrlAlreadyUsed($identity_url) {
        $dar = $this->dao->searchUsersForConnexionString($identity_url);
        if ($dar->count()) {
            return true;
        }
        return false;
    }

    /**
     * Remove all pair done with user account
     */
    public function removePair(PFUser $user) {
        $user_id = $user->getId();
        $dar = $this->dao->searchOpenidUrlsForUserId($user_id);
        if ($dar->count()) {
            $row = $dar->getRow();
            $this->dao->removeConnexionStringForUserId($row['connexion_string'], $user_id);
        }
    }

    /**
     *
     * @param String $identity_url
     * @return PFUser
     */
    public function authenticateCorrespondingUser($identity_url) {
        $users = $this->dao->searchUsersForConnexionString($identity_url)->instanciateWith(array($this->user_manager, 'getUserInstanceFromRow'));
        foreach ($users as $user) {
            $this->user_manager->openSessionForUser($user);
            return $user;
        }
        throw new OpenId_UserNotFoundException($GLOBALS['Language']->getText('plugin_openid', 'error_no_matching_user', Config::get('sys_name')));
    }
}

?>
