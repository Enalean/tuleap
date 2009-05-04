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
require_once('common/widget/Widget.class.php');
require_once('PluginHudsonJobDao.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');

abstract class HudsonWidget extends Widget {
    
    function getCategory() {
        return 'ci';
    }
    
    protected function getAvailableJobs() {
        $jobs = array();
        if ($this->owner_type == WidgetLayoutManager::OWNER_TYPE_USER) {
            $jobs = $this->getJobsByUser($user = UserManager::instance()->getCurrentUser()->getId());
        } else {
            $jobs = $this->getJobsByGroup($this->group_id);
        }
        return $jobs;
    }
    
    protected function getJobsByGroup($group_id) {
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByGroupID($group_id);
        $jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            try {
                $job = new Hudsonjob($row['job_url']);
                $jobs[$row['job_id']] = $job;
            } catch (exception $e) {
                // Do not add unvalid jobs
            }
            $dar->next();
        }
        return $jobs;
    }
    
    protected function getJobsByUser($user_id) {
        $job_dao = new PluginHudsonJobDao(CodendiDataAccess::instance());
        $dar = $job_dao->searchByUserID($user_id);
        $jobs = array();
        while ($dar->valid()) {
            $row = $dar->current();
            try {
                $job = new Hudsonjob($row['job_url']);
                $jobs[$row['job_id']] = $job;
            } catch (exception $e) {
                // Do not add unvalid jobs
            }
            $dar->next();
        }
        return $jobs;
    }
    
    function isAjax() {
        return true;
    }
    
}

?>