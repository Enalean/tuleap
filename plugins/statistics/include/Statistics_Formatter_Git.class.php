<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require_once 'Statistics_Formatter.class.php';
require_once 'Statistics_ScmGitDao.class.php';

/**
 * SCM statistics for Git
 */
class Statistics_Formatter_Git extends Statistics_Formatter {

    protected $dao;

    /**
     * Constructor of the class
     *
     * @param String  $startDate Period start date
     * @param String  $endDate   Period end date
     * @param Integer $groupId   Project Id
     *
     * @return void
     */
    function __construct($startDate, $endDate, $groupId = null) {
        $this->dao = new Statistics_ScmGitDao(CodendiDataAccess::instance(), $this->groupId);
        parent::__construct($startDate, $endDate, $groupId);
    }

    /**
     * git repositories activity evolution
     *
     * @return Array
     */
    function repositoriesEvolutionForPeriod() {
        $dar = $this->dao->totalPushes($this->startDate, $this->endDate);
        $evolution = array();
        if ($dar && !$dar->isError() && $dar->rowCount()> 0) {
            $evolution[] = 'Git total pushes';
            foreach ($dar as $row) {
                $evolution[] = $row['pushes_count'];
            }
        }
        return $evolution;
    }

}

?>