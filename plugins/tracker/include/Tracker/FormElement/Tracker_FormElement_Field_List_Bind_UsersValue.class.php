<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Tracker_FormElement_Field_List_Bind_UsersValue extends Tracker_FormElement_Field_List_BindValue {
    protected $id;
    protected $user_name;
    protected $display_name;
    
    public function __construct($id, $user_name = null, $display_name = null) {
        parent::__construct($id, false);
        $this->user_name    = $user_name;
        $this->display_name = $display_name;
    }
    
    public function getUsername() {
        if ($this->user_name == null) {
            $user = $this->getUser();
            $this->user_name = $user->getUsername();
        }
        return $this->user_name;
    }
    
    public function getLabel() {
        if ($this->display_name) {
            return $this->display_name;
        }
        return $this->getUserHelper()->getDisplayNameFromUserId($this->getId());
    }
    
    protected function getUserHelper() {
        return UserHelper::instance();
    }
    
    public function getUser() {
        return $this->getUserManager()->getUserById($this->getId());
    }
    
    protected function getLink() {
        $display_name = $this->getLabel();
        $user_name    = $this->user_name;
        if (!$user_name) {
            $user_name = $this->getUser()->getUserName();
        }
        return '<a class="direct-link-to-user" href="/users/'. urlencode($user_name) .'">'.
               Codendi_HTMLPurifier::instance()->purify($display_name, CODENDI_PURIFIER_CONVERT_HTML) .
               '</a>';
    }
    
    protected function getUserManager() {
        return UserManager::instance();
    }
    
    public function __toString() {
        return 'Tracker_FormElement_Field_List_Bind_UsersValue #'. $this->getId();
    }
    
    public function fetchFormatted() {
        return $this->getLink();
    }
    
    public function fetchCard($pref = null) {
        if ($this->getId() == 100) {
            return '';
        }

        $user = $this->getUser();
        $current_user = $this->getUserManager()->getCurrentUser();
        if ($current_user->isAnonymous() || ! $current_user->getPreference('AD_cardwall_assign_to_display_username')) {
            return $user->fetchHtmlAvatar(16);
        }
        return $user->fetchHTMLRealName();
    }
    
    public function fetchFormattedForCSV() {
        return $this->getUsername();
    }

    /**
     * @see Tracker_FormElement_Field_List_Value::fetchValuesForJson()
     */
    public function fetchValuesForJson() {
        $json = parent::fetchValuesForJson();
        $json['username'] = $this->getUsername();
        $json['realname'] = $this->getUser()->getRealName();
        return $json;
    }

    public function getSoapValue() {
        return $this->getUsername();
    }
}
?>
