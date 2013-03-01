<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * 
 */

require_once('www/project/admin/permissions.php');

require_once 'common/dao/CodendiDataAccess.class.php';
require_once 'DocmanWatermark_ItemDao.class.php';

class DocmanWatermark_ItemFactory {

    public function getNotWatermarkedByProject($groupId) {
        $dao = $this->getDao();
        return $dao->searchAllItemsNotWatermarked($groupId);
    }
    
    /**
     * Check if watermarking is disabled for item
     * 
     * @param Integer $itemId
     * 
     * @return Boolean
     */
    public function isWatermarkingDisabled($itemId) {
        $dao = $this->getDao();
        return $dao->searchItemNotWatermarked($itemId);
    }

    /**
     * Enable watermarking for item
     * 
     * @param Integer $itemId
     * 
     * @return Boolean
     */
    public function enableWatermarking($itemId) {
        $dao = $this->getDao();
        return $dao->enableWatermarking($itemId);
    }

    /**
     * Disable watermarking for item
     * 
     * @param Integer $itemId
     * 
     * @return Boolean
     */
    public function disableWatermarking($itemId) {
        $dao = $this->getDao();
        return $dao->disableWatermarking($itemId);
    }
    
    /**
     * Send a mail when PDF Watermarking is disabled.
     * 
     * @param Docman_Item $item
     * @param PFUser        $currentUser
     * 
     * @return void
     */
    public function notifyOnDisable($item, $currentUser, $defaultUrl) {
        $admins = $this->getPeopleToNotifyWhenWatermarkingIsDisabled($item);
        
        $link = get_server_url().$defaultUrl.'&action=details&id='.$item->getId();
        
        $mail = new Mail();
        $mail->setTo(implode(',', $admins));
        $mail->setSubject($GLOBALS['Language']->getText('plugin_docmanwatermark', 'email_disable_watermark_subject', array($item->getTitle())));
        $mail->setBody($GLOBALS['Language']->getText('plugin_docmanwatermark', 'email_disable_watermark_body', array($item->getTitle(), $currentUser->getRealname(), $link)));
        $mail->send();
    }
    
    /**
     * Get the list of people to notify when Watermarking is disabled 
     * 
     * Notify the Docman admins.
     * Current code is not really clean, but as there is no clean interface
     * for ugroups & permission manangement...
     * 
     * @return Array
     */
    public function getPeopleToNotifyWhenWatermarkingIsDisabled($item) {
        $res = permission_db_authorized_ugroups('PLUGIN_DOCMAN_ADMIN', $item->getGroupId());
        if(db_numrows($res) == 0) {
            $res = permission_db_get_defaults('PLUGIN_DOCMAN_ADMIN');
        }

        $admins = array();
        $um = UserManager::instance();
        while (($row = db_fetch_array($res))) {
            if ($row['ugroup_id'] < 101) {
                $sql = ugroup_db_get_dynamic_members($row['ugroup_id'], 0, $item->getGroupId());
            } else {
                $sql = ugroup_db_get_members($row['ugroup_id']);
            }
            $res_members = db_query($sql);
            while (($row_members = db_fetch_array($res_members))) {
                $admins[] = $um->getUserById($row_members['user_id'])->getEmail();
            }
        }
        return $admins;
    }
    
    /**
     * Wrapper for DAO
     * 
     * @return DocmanWatermark_ItemDao
     */
    public function getDao() {
        return new DocmanWatermark_ItemDao(CodendiDataAccess::instance());
    }
}

?>