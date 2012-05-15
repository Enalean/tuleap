<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/user/User.class.php';
require_once 'common/project/Project.class.php';
require_once 'common/svn/SVN_RepositoryListing.class.php';

class SVN_RepositoryListingTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $GLOBALS['svn_prefix'] = '/data/svnroot';
    }
    
    public function tearDown() {
        parent::tearDown();
        unset($GLOBALS['svn_prefix']);
    }

    public function itShowsOnlyTheDirectoryContents() {
        $user     = mock('User');
        $project  = stub('Project')->getUnixName()->returns('gpig');
        $svn_path = '/my/Project/tags';
        
        $svn_repo_listing = TestHelper::getPartialMock('SVN_RepositoryListing', array('getDirectoryListing'));
        
        $svn_perms_mgr = stub('SVN_PermissionsManager')->userCanRead()->returns(true);
        $svn_repo_listing->__construct($svn_perms_mgr);

        $content = array("/my/Project/tags",
                         "/my/Project/tags/1.0",
                         "/my/Project/tags/2.0");
        stub($svn_repo_listing)->getDirectoryListing('/data/svnroot/gpig', '/my/Project/tags')->returns($content);
        
        $tags = $svn_repo_listing->getSvnPath($user, $project, $svn_path);
        $this->assertEqual(array_values($tags), array('1.0', '2.0'));
    }
    
    public function itEnsuresUserCannotAccessPathSheIsNotAllowedToSee() {
        $user     = mock('User');
        $project  = stub('Project')->getUnixName()->returns('gpig');
        $svn_path = '/my/Project/tags';
        
        $svn_repo_listing = TestHelper::getPartialMock('SVN_RepositoryListing', array('getDirectoryListing'));
        
        $svn_perms_mgr = stub('SVN_PermissionsManager')->userCanRead($user, $project, '/my/Project/tags/1.0')->returns(true);
        $svn_repo_listing->__construct($svn_perms_mgr);

        $content = array("/my/Project/tags",
                         "/my/Project/tags/1.0",
                         "/my/Project/tags/2.0");
        stub($svn_repo_listing)->getDirectoryListing()->returns($content);

        $tags = $svn_repo_listing->getSvnPath($user, $project, $svn_path);
        $this->assertEqual(array_values($tags), array('1.0'));
    }
    
    public function itExecuteTheCommandOnTheSystem() {
        /*'svnadmin create toto'
        'svn mkdir --parents -m "1.0" file:///.../toto/tags/1.0';
        'svn mkdir --parents -m "1.0" file:///.../toto/tags/2.0';
        */
    }
}

/**
 * Wrapper for svn_get_revisions.
 * 
 * Q: Why is it useful?
 * A: To set expectations in tests :) 
 */
class SVN_RevisionsSource {
    public function getRevisions(&$project, $chunksz, $_commiter = '') {
        // svn_get_revisions($project, $offset, $chunksz, '', $_commiter, '', '', 0, false);
    }
}

function whatever() {
    return new stdClass();
}

class SVN_Repository_CommitListingTest extends TuleapTestCase {
    
    protected $EMPTY_COMMIT_LIST;
    
    public function setUp() {
        parent::setUp();
        
        $this->EMPTY_COMMIT_LIST = array(array(), -1);
        
        $this->user                = mock('User');
        $this->project             = mock('Project');
        $this->revisions_source    = mock('SVN_RevisionsSource');
        $this->permissions_manager = mock('SVN_PermissionsManager');
        $this->repo_listing        = new SVN_RepositoryListing($this->permissions_manager,
                                                               $this->revisions_source);
    }
    
    public function itReturnsMatchingCommitsWhenRequesterHasReadAccessToTheRepository() {
        stub($this->permissions_manager)->userCanRead($this->user, $this->project, '*')->returns(true);
        
        $limit     = 10;
        $author_id = 123;
        $commits   = whatever();
        
        $this->revisions_source->expectOnce('getRevisions', array($this->project, $limit, $author_id));
        stub($this->revisions_source)->getRevisions()->returns($commits);
        
        $actual_commits = $this->repo_listing->getCommits($this->user, $this->project, $limit, $author_id);
        $this->assertIdentical($commits, $actual_commits);
    }
    
    public function itReturnsAnEmptyListWhenRequesterHasNoReadAccessToTheRepository() {
        stub($this->permissions_manager)->userCanRead($this->user, $this->project, '*')->returns(false);
        $this->revisions_source->expectNever('getRevisions');
        $actual_commits = $this->repo_listing->getCommits($this->user, $this->project, whatever(), whatever());
        $this->assertIdentical(array(), $actual_commits);
    }
}

?>
