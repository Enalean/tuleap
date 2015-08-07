<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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
require_once(dirname(__FILE__) . '/../include/lib/PHPWikiPage.class.php');
require_once(dirname(__FILE__).'/../include/PermissionsManager.class.php');

class PHPWiki_PermissionsManagerTestPHP53 extends TuleapTestCase {

    /** @var Wiki_PermissionsManager */
    private $wiki_permissions_manager;

    /** @var PermissionsManager */
    private $permission_manager;

    /** @var ProjectManager */
    private $project_manager;

    /** @var PHPWikiPage */
    private $wiki_page;

    public function skip() {
        $this->skipIfNotPhp53();
    }

    public function setUp() {
        parent::setUp();

        $this->project   = stub('Project')->getUnixName()->returns('perceval');
        stub($this->project)->getId()->returns(200);

        $this->wiki_page = stub('WikiPage')->getId()->returns(101);
        stub($this->wiki_page)->getGid()->returns(200);

        $literalizer              = new UGroupLiteralizer();
        $this->permission_manager = mock('PermissionsManager');
        $this->project_manager    = stub('ProjectManager')->getProject(200)->returns($this->project);

        $this->wiki_permissions_manager = new PHPWiki_PermissionsManager(
            $this->permission_manager,
            $this->project_manager,
            $literalizer
        );
    }

    public function itReturnsPageRights() {
        stub($this->permission_manager)->getAuthorizedUgroupIds(101, 'PHPWIKIPAGE_READ')->returns(array(
            '3', '4', '14', '107'
        ));

        stub($this->permission_manager)->getAuthorizedUgroupIds(200, 'PHPWIKI_READ')->returns(array(
            '2'
        ));

        stub($this->project)->isPublic()->returns(true);

        $expected = array(
            '@perceval_project_members', '@perceval_project_admin', '@perceval_wiki_admin', '@ug_107'
        );

        $this->assertEqual(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function itReturnsServiceRightsIfPageRightsAreWeeker() {
        stub($this->permission_manager)->getAuthorizedUgroupIds(101, 'PHPWIKIPAGE_READ')->returns(array(
            '3'
        ));

        stub($this->permission_manager)->getAuthorizedUgroupIds(200, 'PHPWIKI_READ')->returns(array(
            '4', '14', '107'
        ));

        stub($this->project)->isPublic()->returns(true);

        $expected = array(
            '@perceval_project_admin', '@perceval_wiki_admin', '@ug_107'
        );

        $this->assertEqual(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function itReturnsMixedServiceAndPageRights() {
        stub($this->permission_manager)->getAuthorizedUgroupIds(101, 'PHPWIKIPAGE_READ')->returns(array(
            '107', '108', '4'
        ));

        stub($this->permission_manager)->getAuthorizedUgroupIds(200, 'PHPWIKI_READ')->returns(array(
            '14', '106'
        ));

        stub($this->project)->isPublic()->returns(true);

        $expected = array(
            '@perceval_wiki_admin', '@ug_106', '@perceval_project_admin'
        );

        $this->assertEqual(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function itDoesNotReturnNonMemberUgroupsIfProjectIsPrivate() {
        stub($this->permission_manager)->getAuthorizedUgroupIds(101, 'PHPWIKIPAGE_READ')->returns(array(
            '2'
        ));

        stub($this->permission_manager)->getAuthorizedUgroupIds(200, 'PHPWIKI_READ')->returns(array(
            '2'
        ));

        stub($this->project)->isPublic()->returns(false);

        $expected = array(
            '@perceval_project_admin', '@perceval_wiki_admin'
        );

        $this->assertEqual(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }

    public function itAlwaysReturnsWikiAndProjectAdminGroups() {
        stub($this->permission_manager)->getAuthorizedUgroupIds(101, 'PHPWIKIPAGE_READ')->returns(array(
            '107'
        ));

        stub($this->permission_manager)->getAuthorizedUgroupIds(200, 'PHPWIKI_READ')->returns(array(
            '3'
        ));

        stub($this->project)->isPublic()->returns(true);

        $expected = array(
            '@ug_107', '@perceval_project_admin', '@perceval_wiki_admin'
        );

        $this->assertEqual(
            $expected,
            $this->wiki_permissions_manager->getFromattedUgroupsThatCanReadWikiPage($this->wiki_page)
        );
    }
}