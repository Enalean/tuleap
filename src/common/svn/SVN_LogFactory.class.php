<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'database.php';
require_once 'SVN_LogQuery.class.php';
require_once 'SVN_LogDao.class.php';
require_once 'common/project/Project.class.php';
require_once 'common/svn/SVN_IRevisionDecorator.class.php';
require_once 'common/user/User.class.php';

/**
 * The SVN log of a project.
 */
class SVN_LogFactory {
    
    /**
     * @var Project
     */
    private $project;
    
    /**
     * Builds a new SVN_Log object representing the SVN log of the given
     * project.
     * 
     * @param Project $project 
     */
    public function __construct(Project $project) {
        $this->project = $project;
    }
    
    /**
     * Retrieves SVN revisions matching the given $query.
     * 
     * Revisions are decorated using the given $decorator.
     * 
     * @param SVN_LogQuery       $query
     * @param SVN_IRevisionDecorator $decorator
     * 
     * @return array
     */
    public function getDecoratedRevisions(SVN_LogQuery     $query,
                                          SVN_IRevisionDecorator $decorator) {
        
        $raw_revisions       = $this->getRawRevisions($query);
        $decorated_revisions = array();
        
        while($revision = db_fetch_array($raw_revisions)) {
            $decorated_revisions[] = $decorator->decorate($revision);
        }
        
        return $decorated_revisions;
    }
    
    /**
     * Returns the commiters on given period with their commit count
     * 
     * @param Integer $start_date
     * @param Integer $end_date
     * 
     * @return Array
     */
    public function getCommiters($start_date, $end_date) {
        $this->assertPeriodValidity($start_date, $end_date);
        
        $stats = array();
        $dao   = $this->getDao();
        $dar   = $dao->searchCommiters($this->project->getID(), $start_date, $end_date);
        foreach ($dar as $row) {
            $stats[] = array('user_id' => $row['whoid'], 'commit_count' => $row['commit_count']);
        }
        return $stats;
    }
    
    public function getTopModifiedFiles(User $user, $start_date, $end_date, $limit) {
        $this->assertPeriodValidity($start_date, $end_date);
        if ($limit <= 0) {
            throw new Exception("limit must be a positive number");
        }
        
        $where_forbidden = $this->getForbiddenPaths($user);
        
        $stats = array();
        $dao   = $this->getDao();
        $dar   = $dao->searchTopModifiedFiles($this->project->getID(), $start_date, $end_date, $limit, $where_forbidden);
        foreach ($dar as $row) {
            $stats[] = array('path' => $row['path'], 'commit_count' => $row['commit_count']);
        }
        return $stats;
    }
    
    private function assertPeriodValidity($start_date, $end_date) {
        if ($start_date < 0) {
            throw new Exception('Start date cannot be negative');
        }
        if($end_date <= $start_date) {
            throw new Exception('Start Date must be before End Date');
        }
    }
    
    /**
     * Return SVN path the user is not allowed to see
     * 
     * @param User $user
     * 
     * @return string 
     */
    protected function getForbiddenPaths($user) {
        $forbidden = svn_utils_get_forbidden_paths($user->getName(), $this->project->getUnixName(false));
        $where_forbidden = "";
        foreach ($forbidden as $no_access => $v) {
            $where_forbidden .= " AND svn_dirs.dir not like '".db_es(substr($no_access,1))."%'";
        }
        return $where_forbidden;
    }
    
    /**
     * Same as getRawRevisionsAndCount(), but retrieves only the revisions,
     * without the revisions count.
     * 
     * @param SVN_LogQuery $query
     * 
     * @return mixed Some db result object
     */
    private function getRawRevisions(SVN_LogQuery $query) {
        list($raw_revisions, $count) = $this->getRawRevisionsAndCount($query);
        return $raw_revisions;
    }
    
    /**
     * XXX: USE IN TESTS ONLY !!!
     * 
     * Wraps svn_get_revisions for testing purpose.
     * 
     * @param SVN_LogQuery $query
     * 
     * @return array 
     */
    public function getRawRevisionsAndCount(SVN_LogQuery $query) {
        return svn_get_revisions($this->project,
                                 0,
                                 $query->getLimit(),
                                 '',
                                 $query->getAuthorName(),
                                 '',
                                 '',
                                 0,
                                 false);
    }
    
    protected function getDao() {
        return new SVN_LogDao();
    }
}
?>
