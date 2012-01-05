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

require_once 'Statistics_Scm.class.php';
require_once 'Statistics_ScmCvsDao.class.php';

/**
 * SCM statistics for CVS
 */
class Statistics_ScmCvs extends Statistics_Scm {

    /**
     * Add stats for CVS in CSV format
     *
     * @return String
     */
    function getStats() {
        $dates = $this->splitPeriodByMonths();
        $dao = new Statistics_ScmCvsDao(CodendiDataAccess::instance(), $this->groupId);
        $this->addLine(array('CVS'));
        $periods[]      = $GLOBALS['Language']->getText('plugin_statistics', 'scm_period');
        $totalRead[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_read');
        $totalCommits[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_commit');
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $periods[] = $begin."->".$end;
                $readDar      = $dao->totalRead($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $read = 0;
                    foreach ($readDar as $row) {
                        $read += intval($row['cvs_checkouts + cvs_browse']);
                    }
                    $totalRead[] = $read;
                } else {
                    $totalRead[] = 0;
                }
                $commitsDar = $dao->totalCommits($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $commits = 0;
                    foreach ($commitsDar as $row) {
                        $commits += intval($row['cvs_commits + cvs_adds']);
                    }
                    $totalCommits[] = $commits;
                } else {
                    $totalCommits[] = 0;
                }
            }
        }

        if (!$this->groupId) {
            $readProjectsNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_project');
            $commitProjectsNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_project');
            $rank = 1;
            while ($rank <= 10) {
                $topCommitByProject[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_project')." #".$rank;
                $rank ++;
            }
            foreach ($dates as $begin => $end) {
                if ($begin) {
                    $numberOfReadProjects = 0;
                    $readDar              = $dao->readByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                    if ($readDar && !$readDar->isError()) {
                        $numberOfReadProjects = $readDar->rowCount();
                    }
                    $readProjectsNumber[] = $numberOfReadProjects;

                    $numberOfCommitProjects = 0;
                    $commitsDar             = $dao->commitsByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                    if ($commitsDar && !$commitsDar->isError()) {
                        $rank = 1;
                        while ($rank <= 10) {
                            if ($row = $commitsDar->getRow()) {
                                $topCommitByProject[$rank][] = $row['project'];
                            } else {
                                $topCommitByProject[$rank][] = '';
                            }
                            $rank ++;
                        }
                        $numberOfCommitProjects = $commitsDar->rowCount();
                    }
                    $commitProjectsNumber[] = $numberOfCommitProjects;
                }
            }
        }

        $readUsersNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_user');
        $commitUsersNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_user');
        $rank = 1;
        while ($rank <= 10) {
            $topCommitByUser[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_user')." #".$rank;
            $rank ++;
        }
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $numberOfReadUsers = 0;
                $readDar           = $dao->readByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $numberOfReadUsers = $readDar->rowCount();
                }
                $readUsersNumber[] = $numberOfReadUsers;

                $numberOfCommitUsers = 0;
                $commitsDar          = $dao->commitsByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $rank = 1;
                    while ($rank <= 10) {
                        if ($row = $commitsDar->getRow()) {
                            $topCommitByUser[$rank][] = $row['user'];
                        } else {
                            $topCommitByUser[$rank][] = '';
                        }
                        $rank ++;
                    }
                    $numberOfCommitUsers = $commitsDar->rowCount();
                }
                $commitUsersNumber[] = $numberOfCommitUsers;
            }
        }

        $this->addLine($periods);
        $this->addLine($totalRead);
        if (!$this->groupId) {
            $this->addLine($readProjectsNumber);
        }
        $this->addLine($readUsersNumber);
        $this->addLine($totalCommits);
        if (!$this->groupId) {
            $this->addLine($commitProjectsNumber);
            foreach ($topCommitByProject as $line) {
                $this->addLine($line);
            }
        }
        $this->addLine($commitUsersNumber);
        foreach ($topCommitByUser as $line) {
            $this->addLine($line);
        }

        return $this->content;
    }

}

?>