<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

class Wiki_PermissionsManagerTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Wiki_PermissionsManager */
    private $wiki_permissions_manager;

    /** @var PermissionsManager */
    private $permission_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var WikiPage */
    private $wiki_page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project   = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('perceval')->getMock();
        $this->project->shouldReceive('getId')->andReturns(200);

        $this->wiki_page = \Mockery::spy(\Tuleap\PHPWiki\WikiPage::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $this->wiki_page->shouldReceive('getGid')->andReturns(200);

        $literalizer              = new UGroupLiteralizer();
        $this->permission_manager = \Mockery::spy(\PermissionsManager::class);
        $this->project_manager    = \Mockery::spy(\ProjectManager::class)->shouldReceive('getProject')->with(200)->andReturns($this->project)->getMock();

        $this->wiki_permissions_manager = new Wiki_PermissionsManager(
            $this->permission_manager,
            $this->project_manager,
            $literalizer
        );
    }

    public function testItReturnsPageRights(): void
    {
        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(101, 'WIKIPAGE_READ')->andReturns(array(
            '3', '4', '14', '107'
        ));

        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(200, 'WIKI_READ')->andReturns(array(
            '2'
        ));

        $this->project->shouldReceive('isPublic')->andReturns(true);

        $expected = array(
            '@perceval_project_members', '@perceval_project_admin', '@perceval_wiki_admin', '@ug_107'
        );

        $this->assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItReturnsServiceRightsIfPageRightsAreWeeker(): void
    {
        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(101, 'WIKIPAGE_READ')->andReturns(array(
            '3'
        ));

        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(200, 'WIKI_READ')->andReturns(array(
            '4', '14', '107'
        ));

        $this->project->shouldReceive('isPublic')->andReturns(true);

        $expected = array(
            '@perceval_project_admin', '@perceval_wiki_admin', '@ug_107'
        );

        $this->assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItReturnsMixedServiceAndPageRights(): void
    {
        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(101, 'WIKIPAGE_READ')->andReturns(array(
            '107', '108', '4'
        ));

        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(200, 'WIKI_READ')->andReturns(array(
            '14', '106'
        ));

        $this->project->shouldReceive('isPublic')->andReturns(true);

        $expected = array(
            '@perceval_wiki_admin', '@ug_106', '@perceval_project_admin'
        );

        $this->assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItDoesNotReturnNonMemberUgroupsIfProjectIsPrivate(): void
    {
        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(101, 'WIKIPAGE_READ')->andReturns(array(
            '2'
        ));

        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(200, 'WIKI_READ')->andReturns(array(
            '2'
        ));

        $this->project->shouldReceive('isPublic')->andReturns(false);

        $expected = array(
            '@perceval_project_admin', '@perceval_wiki_admin'
        );

        $this->assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function testItAlwaysReturnsWikiAndProjectAdminGroups(): void
    {
        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(101, 'WIKIPAGE_READ')->andReturns(array(
            '107'
        ));

        $this->permission_manager->shouldReceive('getAuthorizedUgroupIds')->with(200, 'WIKI_READ')->andReturns(array(
            '3'
        ));

        $this->project->shouldReceive('isPublic')->andReturns(true);

        $expected = array(
            '@ug_107', '@perceval_project_admin', '@perceval_wiki_admin'
        );

        $this->assertEquals(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }
}
