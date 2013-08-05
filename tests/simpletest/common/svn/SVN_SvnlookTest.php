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

class SVN_Svnlook_getDirectoryListingTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->svn_prefix = dirname(__FILE__).'/_fixtures';
        $this->project_name = 'svnrepo';
        $this->project = stub('Project')->getUnixName()->returns($this->project_name);
        $this->svnrepo = $this->svn_prefix . '/' . $this->project_name;
        exec("svnadmin create $this->svnrepo");
        exec("svn mkdir --parents -m '1.0' file://$this->svnrepo/tags/1.0");
        exec("svn mkdir --parents -m '2.0' file://$this->svnrepo/tags/2.0");
    }

    public function tearDown() {
        parent::tearDown();
        exec("/bin/rm -rf $this->svnrepo");
    }

    public function itExecuteTheCommandOnTheSystem() {
        $svnlook = new SVN_Svnlook($this->svn_prefix);

        $tags = $svnlook->getDirectoryListing($this->project, '/tags');
        $tags = array_values($tags);
        $tags = sort($tags);
        $this->assertEqual($tags, array('1.0', '2.0'));
    }
}

?>
