<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

class SVN_RepositoryListing_getSvnPathTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->svnlook = mock('SVN_Svnlook');
        $this->svn_perms_mgr = mock('SVN_PermissionsManager');
        $this->user_manager  = mock('UserManager');
        $this->svn_repo_listing = new SVN_RepositoryListing($this->svn_perms_mgr, $this->svnlook, $this->user_manager);
    }

    public function itShowsOnlyTheDirectoryContents()
    {
        $user     = mock('PFUser');
        $project  = stub('Project')->getUnixName()->returns('gpig');
        $svn_path = '/my/Project/tags';

        stub($this->svn_perms_mgr)->userCanRead()->returns(true);

        $content = array("/my/Project/tags",
                         "/my/Project/tags/1.0/",
                         "/my/Project/tags/2.0/");
        stub($this->svnlook)->getDirectoryListing($project, '/my/Project/tags')->returns($content);

        $tags = $this->svn_repo_listing->getSvnPaths($user, $project, $svn_path);
        $this->assertEqual(array_values($tags), array('1.0', '2.0'));
    }

    public function itEnsuresUserCannotAccessPathSheIsNotAllowedToSee()
    {
        $user     = mock('PFUser');
        $project  = stub('Project')->getUnixName()->returns('gpig');
        $svn_path = '/my/Project/tags';

        stub($this->svn_perms_mgr)->userCanRead($user, $project, '/my/Project/tags/1.0/')->returns(true);

        $content = array("/my/Project/tags/",
                         "/my/Project/tags/1.0/",
                         "/my/Project/tags/2.0/");
        stub($this->svnlook)->getDirectoryListing()->returns($content);

        $tags = $this->svn_repo_listing->getSvnPaths($user, $project, $svn_path);
        $this->assertEqual(array_values($tags), array('1.0'));
    }
}

class SVN_RepositoryListing_getSvnPathWithLogDetailsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->order = 'ASC';
        $this->svnlook = mock('SVN_Svnlook');
        $this->svn_perms_mgr = mock('SVN_PermissionsManager');
        $this->user_manager  = mock('UserManager');
        stub($this->svn_perms_mgr)->userCanRead()->returns(true);

        $this->svn_repo_listing = new SVN_RepositoryListing($this->svn_perms_mgr, $this->svnlook, $this->user_manager);

        $this->user     = mock('PFUser');
        $this->project  = stub('Project')->getUnixName()->returns('gpig');

        $content = array("/my/Project/tags",
                         "/my/Project/tags/1.0/",
                         "/my/Project/tags/2.0/");
        stub($this->svnlook)->getDirectoryListing($this->project, '/my/Project/tags')->returns($content);
    }


    public function itReturnsLastRevisionDetails()
    {
        $path           = '/my/Project/tags';
        $author_1       = 'rantanplan';
        $author_1_id    = 458;
        $author_1_user  = stub('PFUser')->getId()->returns($author_1_id);
        $datestamp_1    = '2003-02-22 17:44:49 -0600 (Sat, 22 Feb 2003)';
        $timestamp_1    = 1045957489;
        $log_message_1  = 'Rearrange lunch.';
        $author_2       = 'chucky';
        $author_2_id    = 70;
        $author_2_user  = stub('PFUser')->getId()->returns($author_2_id);
        $datestamp_2    = '2019-08-12 01:01:43 -0900 (Sun, 23 Feb 2003)';
        $timestamp_2    = 1565604103;
        $log_message_2  = 'commit stuff';

        stub($this->user_manager)->getUserByUserName($author_1)->returns($author_1_user);
        stub($this->user_manager)->getUserByUserName($author_2)->returns($author_2_user);

        stub($this->svnlook)->getPathLastHistory($this->project, '/my/Project/tags/1.0')->returns(array(
            'REVISION   PATH',
            '--------   ----',
            '       8  /my/Project/tags/1.0/',
        ));
        stub($this->svnlook)->getPathLastHistory($this->project, '/my/Project/tags/2.0')->returns(array(
            'REVISION   PATH',
            '--------   ----',
            '       19   /my/Project/tags/2.0/',
        ));
        stub($this->svnlook)->getInfo($this->project, '8')->returns(array(
            $author_1,
            $datestamp_1,
            16,
            $log_message_1,
        ));
        stub($this->svnlook)->getInfo($this->project, '19')->returns(array(
            $author_2,
            $datestamp_2,
            16,
            $log_message_2,
        ));

        $last_revision = $this->svn_repo_listing->getSvnPathsWithLogDetails($this->user, $this->project, $path, $this->order);

        $path_info_1 = $last_revision[0];
        $path_info_soap_1 = $path_info_1->exportToSoap();

        $path_info_2 = $last_revision[1];
        $path_info_soap_2 = $path_info_2->exportToSoap();

        $this->assertEqual($path_info_soap_1['author'], $author_1_id);
        $this->assertEqual($path_info_soap_1['message'], $log_message_1);
        $this->assertEqual($path_info_soap_1['timestamp'], $timestamp_1);
        $this->assertEqual($path_info_soap_1['path'], '/my/Project/tags/1.0/');

        $this->assertEqual($path_info_soap_2['author'], $author_2_id);
        $this->assertEqual($path_info_soap_2['message'], $log_message_2);
        $this->assertEqual($path_info_soap_2['timestamp'], $timestamp_2);
        $this->assertEqual($path_info_soap_2['path'], '/my/Project/tags/2.0/');
    }

    public function itReturnsLastRevisionDetailsEvenWhenExactSameTimestamp()
    {
        $path           = '/my/Project/tags';
        $author_1       = 'rantanplan';
        $author_1_id    = 458;
        $author_1_user  = stub('PFUser')->getId()->returns($author_1_id);
        $datestamp_1    = '2003-02-22 17:44:49 -0600 (Sat, 22 Feb 2003)';
        $timestamp_1    = 1045957489;
        $log_message_1  = 'Rearrange lunch.';
        $author_2       = 'chucky';
        $author_2_id    = 70;
        $author_2_user  = stub('PFUser')->getId()->returns($author_2_id);
        $datestamp_2    = '2003-02-22 17:44:49 -0600 (Sat, 22 Feb 2003)';
        $timestamp_2    = 1045957489;
        $log_message_2  = 'commit stuff';

        stub($this->user_manager)->getUserByUserName($author_1)->returns($author_1_user);
        stub($this->user_manager)->getUserByUserName($author_2)->returns($author_2_user);

        stub($this->svnlook)->getPathLastHistory($this->project, '/my/Project/tags/1.0')->returns(array(
            'REVISION   PATH',
            '--------   ----',
            '       8  /my/Project/tags/1.0/',
        ));
        stub($this->svnlook)->getPathLastHistory($this->project, '/my/Project/tags/2.0')->returns(array(
            'REVISION   PATH',
            '--------   ----',
            '       19   /my/Project/tags/2.0/',
        ));
        stub($this->svnlook)->getInfo($this->project, '8')->returns(array(
            $author_1,
            $datestamp_1,
            16,
            $log_message_1,
        ));
        stub($this->svnlook)->getInfo($this->project, '19')->returns(array(
            $author_2,
            $datestamp_2,
            16,
            $log_message_2,
        ));

        $last_revision = $this->svn_repo_listing->getSvnPathsWithLogDetails($this->user, $this->project, $path, $this->order);

        $path_info_1 = $last_revision[0];
        $path_info_soap_1 = $path_info_1->exportToSoap();

        $path_info_2 = $last_revision[1];
        $path_info_soap_2 = $path_info_2->exportToSoap();

        $this->assertEqual($path_info_soap_1['author'], $author_1_id);
        $this->assertEqual($path_info_soap_1['message'], $log_message_1);
        $this->assertEqual($path_info_soap_1['timestamp'], $timestamp_1);
        $this->assertEqual($path_info_soap_1['path'], '/my/Project/tags/1.0/');

        $this->assertEqual($path_info_soap_2['author'], $author_2_id);
        $this->assertEqual($path_info_soap_2['message'], $log_message_2);
        $this->assertEqual($path_info_soap_2['timestamp'], $timestamp_2);
        $this->assertEqual($path_info_soap_2['path'], '/my/Project/tags/2.0/');
    }

    public function itReturnsAnEmptyArrayIfEmptyRepository()
    {
        $svnlook = mock('SVN_Svnlook');
        $svn_repo_listing = new SVN_RepositoryListing($this->svn_perms_mgr, $svnlook, $this->user_manager);

        $content = array('/');
        stub($svnlook)->getDirectoryListing($this->project, '/')->returns($content);

        $last_revision = $svn_repo_listing->getSvnPathsWithLogDetails($this->user, $this->project, '/', $this->order);

        $this->assertTrue(is_array($last_revision));
        $this->assertCount($last_revision, 0);
    }
}
