<?php //-*-php-*-
/* 
 * Copyright© STMicroelectronics, 2006
 *
 * Originally written by Manuel Vacelet, STMicroelectronics, 2006. 
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class _CodexPassUser
extends _PassUser
{
    function _CodexPassUser($UserName='',$prefs=false) {
        if ($prefs) $this->_prefs = $prefs;

        /* Actually, we cannot set preferences here because PhpWiki instanciate
         * _PassUser class before. So we had to modify _PassUser constructor to
        instanciate CodexUserPreferences instead of UserPreferences.
         * if (!$this->_prefs) {
            $this->_prefs = new CodexUserPreferences();
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
            $this->_level = WIKIAUTH_ADMIN; // admin codex
            break;
        default:
            $this->_level = WIKIAUTH_USER;
        }
     
        if(user_ismember(GROUP_ID, 'W2'))
            $this->_level = WIKIAUTH_ADMIN; //admin wiki

        $this->_authmethod = 'Codex';
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

class CodexUserPreferences
extends UserPreferences {

    function CodexUserPreferences($saved_prefs = false) {
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