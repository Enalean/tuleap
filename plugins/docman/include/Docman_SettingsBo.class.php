<?php
/**
 * Copyright � STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * 
 */

require_once('Docman_SettingsDao.class.php');

class Docman_SettingsBo {
    var $row;
    var $groupId;
    var $dao;

    function Docman_SettingsBo($groupId) {
        $this->groupId = $groupId;
        $this->row = null;
        $this->dao = null;
    }

    function &instance($groupId) {
        static $_plugin_docman_settings_bo_i;
        if(!isset($_plugin_docman_settings_bo_i[$groupId])) {
            $_plugin_docman_settings_bo_i[$groupId] = new Docman_SettingsBo($groupId);
        }
        return $_plugin_docman_settings_bo_i[$groupId];
    }

    function &getDao() {
        if($this->dao === null) {
            $this->dao = new Docman_SettingsDao(CodexDataAccess::instance());
        }
        return $this->dao;
    }

    function _cacheGroupSettings() {
        if($this->row === null) {
            $dao =& $this->getDao();
            $dar = $dao->searchByGroupId($this->groupId);
            if($dar && !$dar->isError() && $dar->valid()) {
                $this->row = $dar->current();
            }
        }
    }

    function getView() {
        $this->_cacheGroupSettings();        

        if(isset($this->row['view'])) {
            return $this->row['view'];
        }
        else {
            return false;
        }
    }

    function getMetadataUsage($metadata) {
        $this->_cacheGroupSettings();        

        if(isset($this->row['use_'.$metadata])) {
            return $this->row['use_'.$metadata];
        }
        else {
            return false;
        }
    }

    function settingsExist() {        
        $this->_cacheGroupSettings();
        if($this->row === null) {
            return false;
        }
        else {
            return true;
        }
    }

    function updateView($view) {
        $dao =& $this->getDao();
        if($this->settingsExist()) {
            return $dao->updateViewForGroupId($this->groupId, $view);
        }
        else {
            return $dao->create($this->groupId, $view);
        }
    }
    
    function updateMetadataUsage($label, $useIt) {
        $dao =& $this->getDao();
        if(!$this->settingsExist()) {            
            $dao->create($this->groupId, 'Tree');
        }
        return $dao->updateMetadataUsageForGroupId($this->groupId, $label, $useIt);
    }

    function cloneMetadataSettings($targetGroupId) {
        if($this->settingsExist()) {
            $dao =& $this->getDao();
            $dao->create($targetGroupId,
                         $this->getView(),
                         $this->getMetadataUsage('obsolescence_date'),
                         $this->getMetadataUsage('status'));
        }
    }

    /**
     * Import settings from $srcGroupId.
     *
     * For each metadata, if it's used in the source project but not in the
     * current project, enable it.
     * Note: this doesn't disable metadata not used in the source project but
     * used in current project
     *
     * @access: public
     */
    function importMetadataUsageFrom($srcGroupId) {
        $srcBo =& Docman_SettingsBo::instance($srcGroupId);
        $this->_importMetadataUsage($srcBo, 'obsolescence_date');
        $this->_importMetadataUsage($srcBo, 'status');
    }
    
     /**
      * @access: private
      */
    function _importMetadataUsage(&$srcBo, $label) {
        if($srcBo->getMetadataUsage($label) == true &&
           $this->getMetadataUsage($label) != true) {
            $this->updateMetadataUsage($label, true);
        }
    }
        
}

?>
