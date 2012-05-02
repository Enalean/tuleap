<?php //-*-php-*-
/* 
 * Copyright (c) STMicroelectronics, 2006
 *
 * Originally written by Manuel Vacelet, STMicroelectronics, 2006. 
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class _CodendiPassUser
extends _PassUser
{
    function _CodendiPassUser($UserName='',$prefs=false) {
        if ($prefs) $this->_prefs = $prefs;

        /* Actually, we cannot set preferences here because PhpWiki instanciate
         * _PassUser class before. So we had to modify _PassUser constructor to
        instanciate CodendiUserPreferences instead of UserPreferences.
         * if (!$this->_prefs) {
            $this->_prefs = new CodendiUserPreferences();
            $this->hasHomePage();
            $this->getPreferences();
            print_r($this->_prefs);
        }*/

        $this->_userid = $UserName;
        if (!isset($this->_prefs->_method))
           _PassUser::_PassUser($this->_userid);

        switch($this->_userid) {
        case '':
        case 'NA':
            $this->_level = WIKIAUTH_ANON;
            break;
        case 'admin':
            $this->_level = WIKIAUTH_ADMIN; // admin Codendi
            break;
        default:
            $this->_level = WIKIAUTH_USER;
        }
     
        if(user_ismember(GROUP_ID, 'W2'))
            $this->_level = WIKIAUTH_ADMIN; //admin wiki

        $this->_authmethod = 'Codendi';
    }

    function userExists() {
        return !empty($this->_userid);
    }
    function checkPass($submitted_password) {
        return $this->userExists() and $this->_level > -1;
    }
    function mayChangePass() {
        return false;
    }
}

class CodendiUserPreferences
extends UserPreferences {

    function CodendiUserPreferences($saved_prefs = false) {
        $this->UserPreferences($saved_prefs);
        //        $this->set('emailVerified', 1);
        //$this->set('email', user_getemail(user_getid()));
    }

    function get($name) {
        if ($name == 'emailVerified') {
            return 1;
        }
        if ($name == 'email') {
            return user_getemail(user_getid());
        }
        return parent::get($name);
    }


}

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>