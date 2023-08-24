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
require_once 'Statistics_ScmSvnDao.class.php';
require_once 'Statistics_Formatter_Scm.class.php';

/**
 * SCM statistics for SVN
 */
class Statistics_Formatter_Svn extends Statistics_Formatter_Scm
{
    /**
     * Constructor of the class
     *
     * @param String  $startDate Period start date
     * @param String  $endDate   Period end date
     * @param int $groupId Project Id
     *
     * @return void
     */
    public function __construct($startDate, $endDate, $groupId = null)
    {
        $this->dao = new Statistics_ScmSvnDao(CodendiDataAccess::instance(), $this->groupId);
        parent::__construct($startDate, $endDate, $groupId);
    }

    /**
     * Add stats for SVN in CSV format
     *
     * @return String
     */
    public function getStats()
    {
        $this->addHeader('SVN');
        return parent::getStats();
    }
}
