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
require_once 'common/project/Project.class.php';
require_once 'common/versioning/IRevisionDecorator.class.php';

/**
 * The SVN log of a project.
 */
class SVN_Log {
    
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
     * @param IRevisionDecorator $decorator
     * 
     * @return array
     */
    public function getDecoratedRevisions(SVN_LogQuery     $query,
                                          IRevisionDecorator $decorator) {
        
        $raw_revisions       = $this->getRawRevisions($query);
        $decorated_revisions = array();
        
        while($revision = db_fetch_array($raw_revisions)) {
            $decorated_revisions[] = $decorator->decorate($revision);
        }
        
        return $decorated_revisions;
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
}
?>
