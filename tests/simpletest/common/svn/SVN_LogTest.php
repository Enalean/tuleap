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

require_once 'common/project/Project.class.php';
require_once 'common/svn/SVN_Log.class.php';

class SVN_LogTest_NullDecorator implements IRevisionDecorator {
    public function decorate(array $revision) {
        return $revision;
    }
}

class SVN_LogTest_Commiters extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->svn_log = TestHelper::getPartialMock('SVN_Log', array('getDao'));
        
        $this->dao = stub('SVN_LogDao')->searchCommiters()->returns(array());
        stub($this->svn_log)->getDao()->returns($this->dao);
        
        $this->svn_log->__construct(mock('Project'));
    }
    
    function itFetchFromDatabaseWithValidrequest() {
        $start_date = 0;
        $end_date   = 200;
        
        $this->dao->expectOnce('searchCommiters', array('*', $start_date, $end_date));
        
        $this->svn_log->getCommiters($start_date, $end_date);
    }
    
    function itThrowAnExceptionWhenStartDateIsNegative() {
        $start_date = -1;
        $end_date   = 200;
        
        $this->expectException();
        
        $this->svn_log->getCommiters($start_date, $end_date);
    }
    
    
    function itThrowAnExceptionWhenEndDateIsBeforeStartDate() {
        $start_date = 200;
        $end_date   = 100;
        
        $this->expectException();
        
        $this->svn_log->getCommiters($start_date, $end_date);
    }
    
    function itThrowAnExceptionWhenPeriodIsNull() {
        $start_date = 200;
        $end_date   = 200;
        
        $this->expectException();
        
        $this->svn_log->getCommiters($start_date, $end_date);
    }
}

class SVN_LogTest extends TuleapTestCase {
//    public function itDelegatesSvnRevisionsRetrievalTo_svn_get_revisions() {
//        
//        
//        $limit     = null;
//        $author_id = '';
//        $query     = new SVN_LogQuery($limit, $author_id);
//        $decorator = new SVN_LogTest_NullDecorator();
//        $revisions = array(array('3', '3', 'Added makefile',    '1337144142', '109'),
//                           array('2', '2', 'Added main module', '1337141908', '108'),
//                           array('1', '1', 'Added README',      '1337140135', '108'),
//                           -1);
//        
//        $project = mock('Project');
//        $svn_log = TestHelper::getPartialMock('SVN_Log', array('getRawRevisionsAndCount'));
//        $svn_log->__construct($project);
//        
//        $svn_log->expectOnce('getRawRevisionsAndCount', array($query));
//        stub($svn_log)->getRawRevisionsAndCount()->returns($revisions);
//        
//        $this->assertIdentical($revisions,
//                               $svn_log->getDecoratedRevisions($query, $decorator));
//    }
    
//    public function itMayBeEmpty() {}
//    public function itMayFilterByAuthor() {}
//    public function itMayLimitsTheResults() {}
}

?>
