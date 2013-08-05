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
        $this->svnlook = mock('SVN_Svnlook');
        $this->svn_perms_mgr = mock('SVN_PermissionsManager');
        $this->svn_repo_listing = new SVN_RepositoryListing($this->svn_perms_mgr, $this->svnlook);
    }

    public function itShowsOnlyTheDirectoryContents() {
        $user     = mock('PFUser');
        $project  = stub('Project')->getUnixName()->returns('gpig');
        $svn_path = '/my/Project/tags';

        stub($this->svn_perms_mgr)->userCanRead()->returns(true);

        $content = array("/my/Project/tags",
                         "/my/Project/tags/1.0/",
                         "/my/Project/tags/2.0/");
        stub($this->svnlook)->getDirectoryListing($project, '/my/Project/tags')->returns($content);

        $tags = $this->svn_repo_listing->getSvnPath($user, $project, $svn_path);
        $this->assertEqual(array_values($tags), array('1.0', '2.0'));
    }

    public function itEnsuresUserCannotAccessPathSheIsNotAllowedToSee() {
        $user     = mock('PFUser');
        $project  = stub('Project')->getUnixName()->returns('gpig');
        $svn_path = '/my/Project/tags';

        stub($this->svn_perms_mgr)->userCanRead($user, $project, '/my/Project/tags/1.0/')->returns(true);

        $content = array("/my/Project/tags/",
                         "/my/Project/tags/1.0/",
                         "/my/Project/tags/2.0/");
        stub($this->svnlook)->getDirectoryListing()->returns($content);

        $tags = $this->svn_repo_listing->getSvnPath($user, $project, $svn_path);
        $this->assertEqual(array_values($tags), array('1.0'));
    }
}

?>
