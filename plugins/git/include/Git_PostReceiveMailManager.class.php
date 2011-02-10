<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

require_once('Git_PostReceiveMailDao.class.php');

class Git_PostReceiveMailManager {

    var $dao;

    /*
     * Constructor of the class
     *
     * @return void
     */
    function __construct() {
        $this->dao = $this->_getDao();
    }

    /*
     * Add a mail address to a repository to be notified
     */
    function addMail($repositoryId, $mail) {
    }

    /*
     * Remove a notified mail address from a repository
     */
    function removeMailByRepository($repositoryId, $mail) {
    }

    /*
     * Remove a notified mail address from all repositories of a project
     */
    function removeMailByProject($groupId, $mail) {
    }

    /**
     * Obtain an instance of Git_PostReceiveMailDao
     *
     * @return Git_PostReceiveMailDao
     */
    function _getDao() {
        if (!$this->dao) {
            $this->dao = new Git_PostReceiveMailDao(CodendiDataAccess::instance());
        }
        return  $this->dao;
    }

}

?>