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

class SVN_LogQueryTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->author = mock('User');
        stub($this->author)->getId()->returns(108);
        stub($this->author)->getUserName()->returns('john');
        
        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserById($this->author->getId())
                                 ->returns($this->author);
    }
    
    public function itWrapsCriteriaForSvnRevisionsRetrieval() {
        $limit = 10;
        $query = new SVN_LogQuery($limit, $this->author->getId());
        
        $query->setUserManager($this->user_manager);
        
        $this->assertEqual($query->getLimit(), $limit);
        $this->assertEqual($query->getAuthorName(), $this->author->getUserName());
    }
    
    public function itSetsLimitTo50ByDefault() {
        $query = new SVN_LogQuery(null, 123);
        $this->assertEqual($query->getLimit(), 50);
    }
    
    public function itSetsAuthorIdToAnEmptyStringByDefault() {
        $query = new SVN_LogQuery(123, null);
        $this->assertEqual($query->getAuthorName(), '');
    }
}
?>
