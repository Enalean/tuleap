<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

require_once('Docman_SettingsDao.class.php');

class Docman_SettingsBo
{
    public $row;
    public $groupId;
    public $dao;

    public function __construct($groupId)
    {
        $this->groupId = $groupId;
        $this->row = null;
        $this->dao = null;
    }

    public static function instance($groupId)
    {
        static $_plugin_docman_settings_bo_i;
        if (!isset($_plugin_docman_settings_bo_i[$groupId])) {
            $_plugin_docman_settings_bo_i[$groupId] = new Docman_SettingsBo($groupId);
        }
        return $_plugin_docman_settings_bo_i[$groupId];
    }

    public function &getDao()
    {
        if ($this->dao === null) {
            $this->dao = new Docman_SettingsDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    public function _cacheGroupSettings()
    {
        if ($this->row === null) {
            $dao = $this->getDao();
            $dar = $dao->searchByGroupId($this->groupId);
            if ($dar && !$dar->isError() && $dar->valid()) {
                $this->row = $dar->current();
            }
        }
    }

    public function getView()
    {
        $this->_cacheGroupSettings();

        if (isset($this->row['view'])) {
            return $this->row['view'];
        } else {
            return false;
        }
    }

    public function getMetadataUsage($metadata)
    {
        $this->_cacheGroupSettings();

        if (isset($this->row['use_' . $metadata])) {
            return $this->row['use_' . $metadata];
        } else {
            return false;
        }
    }

    public function settingsExist()
    {
        $this->_cacheGroupSettings();
        if ($this->row === null) {
            return false;
        } else {
            return true;
        }
    }

    public function updateView($view)
    {
        $dao = $this->getDao();
        if ($this->settingsExist()) {
            return $dao->updateViewForGroupId($this->groupId, $view);
        } else {
            return $dao->create($this->groupId, $view);
        }
    }

    public function updateMetadataUsage($label, $useIt)
    {
        $dao = $this->getDao();
        if (!$this->settingsExist()) {
            $dao->create($this->groupId, 'Tree');
        }
        return $dao->updateMetadataUsageForGroupId($this->groupId, $label, $useIt);
    }

    public function cloneMetadataSettings($targetGroupId)
    {
        if ($this->settingsExist()) {
            $dao = $this->getDao();
            $dao->create(
                $targetGroupId,
                $this->getView(),
                $this->getMetadataUsage('obsolescence_date'),
                $this->getMetadataUsage('status')
            );
        }
    }

    /**
     * Export metadata usage into destination project
     *
     * For each metadata, if it's used in the current project but not in the
     * destination one, enable it.
     * Note: this doesn't disable metadata not in use in the current project but
     * in use in destination one.
     *
     * @access: public
     */
    public function exportMetadataUsage($dstGroupId)
    {
        $dstBo = Docman_SettingsBo::instance($dstGroupId);
        $dstBo->_importMetadataUsage($this, 'obsolescence_date');
        $dstBo->_importMetadataUsage($this, 'status');
    }

     /**
      * @access: private
      */
    public function _importMetadataUsage($srcBo, $label)
    {
        if ($srcBo->getMetadataUsage($label) == true &&
           $this->getMetadataUsage($label) != true) {
            $this->updateMetadataUsage($label, true);
        }
    }
}
