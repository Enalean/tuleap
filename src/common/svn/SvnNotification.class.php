<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

class SvnNotification
{

    /**
     * Obtain an instance of SvnNotificationDao
     *
     * @return SvnNotificationDao
     */
    public function _getDao()
    {
        if (!isset($this->_dao)) {
            $this->_dao = new SvnNotificationDao(CodendiDataAccess::instance());
        }
        return $this->_dao;
    }

    /**
     * Set mailing list notification per path
     *
     * @param int $projectId Project id
     * @param String  $mailingList List of mail addresses
     * @param String  $path        svn path
     *
     * @return bool
     */
    public function setSvnMailingList($projectId, $mailingList, $path)
    {
        $dao = $this->_getDao();
        return $dao->setSvnMailingList($projectId, $mailingList, $path);
    }

    /**
     * Get mailing list notification per path
     *
     * @param int $projectId Project id
     * @param String  $path      svn path
     *
     * @return String
     */
    public function getSvnMailingList($projectId, $path)
    {
        $dao = $this->_getDao();
        $dar = $dao->getSvnMailingList($projectId, $path);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            return $row['svn_events_mailing_list'];
        } else {
            return '';
        }
    }

    /**
     * Get mailing list notification and path for the whole project
     *
     * @param int $projectId Project id
     *
     * @return DataAccessResult
     */
    public function getSvnEventNotificationDetails($projectId)
    {
        $dao = $this->_getDao();
        $dar = $dao->getSvnMailingList($projectId);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return $dar;
        } else {
            return null;
        }
    }

    /**
     * Remove svn notification details
     *
     * @param int $projectId Project id
     * @param Array   $selectedPaths Contains list of paths to remove.
     *
     * @return void
     */
    public function removeSvnNotification($projectId, $selectedPaths)
    {
        if (is_array($selectedPaths) && !empty($selectedPaths)) {
            $dao = $this->_getDao();
            $paths = array();
            foreach ($selectedPaths as $pathToDelete) {
                if ($dao->deleteSvnMailingList($projectId, $pathToDelete)) {
                    $paths[] = $pathToDelete;
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('svn_admin_notification', 'delete_path_fail'));
                }
            }
            if (!empty($paths)) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('svn_admin_notification', 'delete_path_success', array(implode(',', $paths))));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('svn_admin_notification', 'retrieve_paths_fail'));
        }
    }
}
