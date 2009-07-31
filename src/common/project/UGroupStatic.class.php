<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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

require_once 'UGroup.class.php';
require_once 'common/dao/UGroupStaticDao.class.php';

class UGroupStatic extends UGroup {

    /*function addUser($user) {
        $dao       = $this->getDao();
        $userAdded = $dao->addUser($this->id, $user->getId());
        if ($userAdded) {
            // Now log in project history
            group_add_history('upd_ug', '', $this->group_id, array($this->name));
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_ugroup_utils','ug_upd_success',array($this->name, 1)));

            // Raise event for ugroup modification
            $this->getEventManager()->processEvent('project_admin_ugroup_add_user', array(
                'group_id'  => $this->groupId,
                'ugroup_id' => $this->id,
                'user_id'   => $user->getId()));
        }
    }*/
    
    /**
     * Wrapper for
     * 
     * @return UGroupStaticDao
     */
    function getDao() {
        return new UGroupStaticDao(CodendiDataAccess::instance());
    }
    

}

?>