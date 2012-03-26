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

require_once('Git_LogDao.class.php');

class GitLog {
    /**
     * @var Git_LogDao
     */
    private $dao;

    public function __construct() {
         $this->dao = new Git_LogDao(CodendiDataAccess::instance());
    }

    /**
     *  Returns the SQL request & form field for the Git pushes
     * @param $params
     */
    function logsDaily($params) {
            $params['logs'][] = array(
            'sql'   => $this->dao->getSqlStatementForLogsDaily($params['group_id'], $params['logs_cond']),
            'field' => $GLOBALS['Language']->getText('plugin_git','logsdaily_field'),
            'title' => $GLOBALS['Language']->getText('plugin_git','logsdaily_title')
        );
    }
}
?>
