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
class SVN_RepositoryListingGetSvnPathTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svnlook = \Mockery::spy(\SVN_Svnlook::class);
        $this->svn_perms_mgr = \Mockery::spy(\SVN_PermissionsManager::class);
        $this->user_manager  = \Mockery::spy(\UserManager::class);
        $this->svn_repo_listing = new SVN_RepositoryListing($this->svn_perms_mgr, $this->svnlook, $this->user_manager);
    }

    public function testItShowsOnlyTheDirectoryContents(): void
    {
        $user     = \Mockery::spy(\PFUser::class);
        $project  = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('gpig')->getMock();
        $svn_path = '/my/Project/tags';

        $this->svn_perms_mgr->shouldReceive('userCanRead')->andReturns(true);

        $content = ["/my/Project/tags",
            "/my/Project/tags/1.0/",
            "/my/Project/tags/2.0/"];
        $this->svnlook->shouldReceive('getDirectoryListing')->with($project, '/my/Project/tags')->andReturns($content);

        $tags = $this->svn_repo_listing->getSvnPaths($user, $project, $svn_path);
        $this->assertEquals(['1.0', '2.0'], array_values($tags));
    }

    public function testItEnsuresUserCannotAccessPathSheIsNotAllowedToSee(): void
    {
        $user     = \Mockery::spy(\PFUser::class);
        $project  = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('gpig')->getMock();
        $svn_path = '/my/Project/tags';

        $this->svn_perms_mgr->shouldReceive('userCanRead')->with($user, $project, '/my/Project/tags/1.0/')->andReturns(true);

        $content = ["/my/Project/tags/",
            "/my/Project/tags/1.0/",
            "/my/Project/tags/2.0/"];
        $this->svnlook->shouldReceive('getDirectoryListing')->andReturns($content);

        $tags = $this->svn_repo_listing->getSvnPaths($user, $project, $svn_path);
        $this->assertEquals(['1.0'], array_values($tags));
    }
}
