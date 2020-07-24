<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SVN_RepositoryListingGetSvnPathWithLogDetailsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->order = 'ASC';
        $this->svnlook = \Mockery::spy(\SVN_Svnlook::class);
        $this->svn_perms_mgr = \Mockery::spy(\SVN_PermissionsManager::class);
        $this->user_manager  = \Mockery::spy(\UserManager::class);
        $this->svn_perms_mgr->shouldReceive('userCanRead')->andReturns(true);

        $this->svn_repo_listing = new SVN_RepositoryListing($this->svn_perms_mgr, $this->svnlook, $this->user_manager);

        $this->user     = \Mockery::spy(\PFUser::class);
        $this->project  = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('gpig')->getMock();

        $content = [
            "/my/Project/tags",
            "/my/Project/tags/1.0/",
            "/my/Project/tags/2.0/"
        ];

        $this->svnlook->shouldReceive('getDirectoryListing')->with($this->project, '/my/Project/tags')->andReturns($content);
    }

    public function testItReturnsLastRevisionDetails(): void
    {
        $path           = '/my/Project/tags';
        $author_1       = 'rantanplan';
        $author_1_id    = 458;
        $author_1_user  = \Mockery::spy(\PFUser::class)->shouldReceive('getId')->andReturns($author_1_id)->getMock();
        $datestamp_1    = '2003-02-22 17:44:49 -0600 (Sat, 22 Feb 2003)';
        $timestamp_1    = 1045957489;
        $log_message_1  = 'Rearrange lunch.';
        $author_2       = 'chucky';
        $author_2_id    = 70;
        $author_2_user  = \Mockery::spy(\PFUser::class)->shouldReceive('getId')->andReturns($author_2_id)->getMock();
        $datestamp_2    = '2019-08-12 01:01:43 -0900 (Sun, 23 Feb 2003)';
        $timestamp_2    = 1565604103;
        $log_message_2  = 'commit stuff';

        $this->user_manager->shouldReceive('getUserByUserName')->with($author_1)->andReturns($author_1_user);
        $this->user_manager->shouldReceive('getUserByUserName')->with($author_2)->andReturns($author_2_user);

        $this->svnlook->shouldReceive('getPathLastHistory')->with($this->project, '/my/Project/tags/1.0')->andReturns([
            'REVISION   PATH',
            '--------   ----',
            '       8  /my/Project/tags/1.0/',
        ]);
        $this->svnlook->shouldReceive('getPathLastHistory')->with($this->project, '/my/Project/tags/2.0')->andReturns([
            'REVISION   PATH',
            '--------   ----',
            '       19   /my/Project/tags/2.0/',
        ]);
        $this->svnlook->shouldReceive('getInfo')->with($this->project, '8')->andReturns([
            $author_1,
            $datestamp_1,
            16,
            $log_message_1,
        ]);
        $this->svnlook->shouldReceive('getInfo')->with($this->project, '19')->andReturns([
            $author_2,
            $datestamp_2,
            16,
            $log_message_2,
        ]);

        $last_revision = $this->svn_repo_listing->getSvnPathsWithLogDetails($this->user, $this->project, $path, $this->order);

        $path_info_1 = $last_revision[0];
        $path_info_soap_1 = $path_info_1->exportToSoap();

        $path_info_2 = $last_revision[1];
        $path_info_soap_2 = $path_info_2->exportToSoap();

        $this->assertEquals($author_1_id, $path_info_soap_1['author']);
        $this->assertEquals($log_message_1, $path_info_soap_1['message']);
        $this->assertEquals($timestamp_1, $path_info_soap_1['timestamp']);
        $this->assertEquals('/my/Project/tags/1.0/', $path_info_soap_1['path']);

        $this->assertEquals($author_2_id, $path_info_soap_2['author']);
        $this->assertEquals($log_message_2, $path_info_soap_2['message']);
        $this->assertEquals($timestamp_2, $path_info_soap_2['timestamp']);
        $this->assertEquals('/my/Project/tags/2.0/', $path_info_soap_2['path']);
    }

    public function testItReturnsLastRevisionDetailsEvenWhenExactSameTimestamp(): void
    {
        $path           = '/my/Project/tags';
        $author_1       = 'rantanplan';
        $author_1_id    = 458;
        $author_1_user  = \Mockery::spy(\PFUser::class)->shouldReceive('getId')->andReturns($author_1_id)->getMock();
        $datestamp_1    = '2003-02-22 17:44:49 -0600 (Sat, 22 Feb 2003)';
        $timestamp_1    = 1045957489;
        $log_message_1  = 'Rearrange lunch.';
        $author_2       = 'chucky';
        $author_2_id    = 70;
        $author_2_user  = \Mockery::spy(\PFUser::class)->shouldReceive('getId')->andReturns($author_2_id)->getMock();
        $datestamp_2    = '2003-02-22 17:44:49 -0600 (Sat, 22 Feb 2003)';
        $timestamp_2    = 1045957489;
        $log_message_2  = 'commit stuff';

        $this->user_manager->shouldReceive('getUserByUserName')->with($author_1)->andReturns($author_1_user);
        $this->user_manager->shouldReceive('getUserByUserName')->with($author_2)->andReturns($author_2_user);

        $this->svnlook->shouldReceive('getPathLastHistory')->with($this->project, '/my/Project/tags/1.0')->andReturns([
            'REVISION   PATH',
            '--------   ----',
            '       8  /my/Project/tags/1.0/',
        ]);
        $this->svnlook->shouldReceive('getPathLastHistory')->with($this->project, '/my/Project/tags/2.0')->andReturns([
            'REVISION   PATH',
            '--------   ----',
            '       19   /my/Project/tags/2.0/',
        ]);
        $this->svnlook->shouldReceive('getInfo')->with($this->project, '8')->andReturns([
            $author_1,
            $datestamp_1,
            16,
            $log_message_1,
        ]);
        $this->svnlook->shouldReceive('getInfo')->with($this->project, '19')->andReturns([
            $author_2,
            $datestamp_2,
            16,
            $log_message_2,
        ]);

        $last_revision = $this->svn_repo_listing->getSvnPathsWithLogDetails($this->user, $this->project, $path, $this->order);

        $path_info_1 = $last_revision[0];
        $path_info_soap_1 = $path_info_1->exportToSoap();

        $path_info_2 = $last_revision[1];
        $path_info_soap_2 = $path_info_2->exportToSoap();

        $this->assertEquals($author_1_id, $path_info_soap_1['author']);
        $this->assertEquals($log_message_1, $path_info_soap_1['message']);
        $this->assertEquals($timestamp_1, $path_info_soap_1['timestamp']);
        $this->assertEquals('/my/Project/tags/1.0/', $path_info_soap_1['path']);

        $this->assertEquals($author_2_id, $path_info_soap_2['author']);
        $this->assertEquals($log_message_2, $path_info_soap_2['message']);
        $this->assertEquals($timestamp_2, $path_info_soap_2['timestamp']);
        $this->assertEquals('/my/Project/tags/2.0/', $path_info_soap_2['path']);
    }

    public function testItReturnsAnEmptyArrayIfEmptyRepository(): void
    {
        $svnlook = \Mockery::spy(\SVN_Svnlook::class);
        $svn_repo_listing = new SVN_RepositoryListing($this->svn_perms_mgr, $svnlook, $this->user_manager);

        $content = ['/'];
        $svnlook->shouldReceive('getDirectoryListing')->with($this->project, '/')->andReturns($content);

        $last_revision = $svn_repo_listing->getSvnPathsWithLogDetails($this->user, $this->project, '/', $this->order);

        $this->assertTrue(is_array($last_revision));
        $this->assertCount(0, $last_revision);
    }
}
