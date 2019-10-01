<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

Mock::generate('BaseLanguage');

Mock::generate('ReferenceDao');
Mock::generate('CrossReferenceDao');
Mock::generate('DataAccessResult');

class ReferenceManagerTest extends TuleapTestCase
{
    private $rm;
    private $user_manager;

    public function setUp()
    {
        parent::setUp();
        EventManager::setInstance(\Mockery::spy(\EventManager::class));
        ProjectManager::setInstance(mock('ProjectManager'));
        $this->user_manager = mock('UserManager');
        UserManager::setInstance($this->user_manager);
        $this->rm = partial_mock('ReferenceManager', array(
            '_getReferenceDao',
            '_getCrossReferenceDao',
            'loadReservedKeywords',
            'getGroupIdFromArtifactIdForCallbackFunction',
            'getGroupIdFromArtifactId'
        ));
        $this->rm->__construct();
    }

    public function tearDown()
    {
        EventManager::clearInstance();
        ProjectManager::clearInstance();
        UserManager::clearInstance();
        parent::tearDown();
    }

    function testSingleton()
    {
        $this->assertEqual(ReferenceManager::instance(), ReferenceManager::instance());
        $this->assertIsA(ReferenceManager::instance(), 'ReferenceManager');
    }

    function testExtractReference()
    {
        $dao = new MockReferenceDao($this);
        $dar = new MockDataAccessResult($this);

        $dao->setReturnReference('searchActiveByGroupID', $dar, array('100'));
        $dar->setReturnValueAt(0, 'getRow', array(
            'id' => 1,
            'keyword' => 'art',
            'description' => 'reference_art_desc_key',
            'link' => '/tracker/?func=detail&aid=$1&group_id=$group_id',
            'scope' => 'S',
            'service_short_name' => 'tracker',
            'nature' => 'artifact',
            'id' => 1,
            'reference_id' => 1,
            'group_id' => 100,
            'is_active' => 1,
        ));
        $dar->setReturnValueAt(1, 'getRow', false);

        $dar2 = new MockDataAccessResult($this);
        $dao->setReturnReference('searchActiveByGroupID', $dar2, array('1'));
        $dar2->setReturnValueAt(0, 'getRow', array(
            'id' => 1,
            'keyword' => 'art',
            'description' => 'reference_art_desc_key',
            'link' => '/tracker/?func=detail&aid=$1&group_id=$group_id',
            'scope' => 'S',
            'service_short_name' => 'tracker',
            'nature' => 'artifact',
            'id' => 1,
            'reference_id' => 1,
            'group_id' => 1,
            'is_active' => 1,
        ));
        $dar2->setReturnValueAt(1, 'getRow', false);
        //The Reference manager

        $this->rm->setReturnReference('_getReferenceDao', $dao);
        $this->rm->setReturnValueAt(0, 'getGroupIdFromArtifactIdForCallbackFunction', '100');
        $this->rm->setReturnValueAt(1, 'getGroupIdFromArtifactIdForCallbackFunction', '1');
        $this->rm->setReturnValueAt(2, 'getGroupIdFromArtifactIdForCallbackFunction', '100');

        $this->assertTrue(count($this->rm->extractReferences("art #123", 0)) == 1, "Art is a shared keyword for all projects");
        $this->assertTrue(count($this->rm->extractReferences("arto #123", 0)) == 0, "Should not extract a reference on unknown keyword");
        $this->assertTrue(count($this->rm->extractReferences("art #1:123", 0)) == 1, "Art is a reference for project num 1");
        $this->assertTrue(count($this->rm->extractReferences("art #100:123", 0)) == 1, "Art is a reference for project named codendi");
    }

    function testExtractRegexp()
    {
        $dao = new MockReferenceDao($this);
        //The Reference manager
        $this->rm->setReturnReference('_getReferenceDao', $dao);
        $this->assertFalse(count($this->rm->_extractAllMatches("art 123")) == 1, "No sharp sign");
        $this->assertFalse(count($this->rm->_extractAllMatches("art#123")) == 1, "No space");
        $this->assertFalse(count($this->rm->_extractAllMatches("art #")) == 1, "No reference");

        $this->assertTrue(count($this->rm->_extractAllMatches("art #123")) == 1, "simple reference");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #abc")) == 1, "No number");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #abc:123")) == 1, "groupName:ObjID");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #123:123")) == 1, "groupID:ObjID");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #abc:abc")) == 1, "groupName:ObjName");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #123:abc")) == 1, "groupID:ObjName");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #123:abc is a reference to art #123 and rev #codendi:123 as well as file #123:release1", 0)) == 4, "Multiple extracts");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #123-rev #123", 0)) == 2, "Multiple extracts with '-'");
        $this->assertTrue(count($this->rm->_extractAllMatches("art #123:wikipage/2", 0)) == 1, "Wikipage revision number");

        // Projectname with - and _ See SR #1178
        $matches = $this->rm->_extractAllMatches("art #abc-def:ghi");
        $this->assertEqual($matches[0]['project_name'], 'abc-def:');
        $this->assertEqual($matches[0]['value'], 'ghi');
        $matches = $this->rm->_extractAllMatches("art #abc-de_f:ghi");
        $this->assertEqual($matches[0]['project_name'], 'abc-de_f:');
        $this->assertEqual($matches[0]['value'], 'ghi');

        // SR #2353 - Reference to wiki page name with "&" does not work
        $matches = $this->rm->_extractAllMatches('wiki #project:page/subpage&amp;toto&tutu & co');
        $this->assertEqual($matches[0]['key'], 'wiki');
        $this->assertEqual($matches[0]['project_name'], 'project:');
        $this->assertEqual($matches[0]['value'], 'page/subpage&amp;toto&tutu');

        $matches = $this->rm->_extractAllMatches('Merge &#039;ref #12784&#039; into stable/master');
        $this->assertCount($matches, 1);
        $this->assertEqual($matches[0]['key'], 'ref');
        $this->assertEqual($matches[0]['value'], '12784');

        $matches = $this->rm->_extractAllMatches('Merge &#039;ref #12784&#039; for doc #123');
        $this->assertCount($matches, 2);
        $this->assertEqual($matches[0]['key'], 'ref');
        $this->assertEqual($matches[0]['value'], '12784');
        $this->assertEqual($matches[1]['key'], 'doc');
        $this->assertEqual($matches[1]['value'], '123');

        $matches = $this->rm->_extractAllMatches('Merge &#x27;ref #12784&#x27; for doc #123');
        $this->assertCount($matches, 2);
        $this->assertEqual($matches[0]['key'], 'ref');
        $this->assertEqual($matches[0]['value'], '12784');
        $this->assertEqual($matches[1]['key'], 'doc');
        $this->assertEqual($matches[1]['value'], '123');

        $matches = $this->rm->_extractAllMatches('Merge &quot;ref #12784&quot; for doc #123');
        $this->assertCount($matches, 2);
        $this->assertEqual($matches[0]['key'], 'ref');
        $this->assertEqual($matches[0]['value'], '12784');
        $this->assertEqual($matches[1]['key'], 'doc');
        $this->assertEqual($matches[1]['value'], '123');

        $matches = $this->rm->_extractAllMatches('See ref #12784.');
        $this->assertCount($matches, 1);
        $this->assertEqual($matches[0]['key'], 'ref');
        $this->assertEqual($matches[0]['value'], '12784');
    }

    function test_updateProjectReferenceShortName()
    {
        $ref_dao = new MockReferenceDao($this);
        $cross_dao = new MockCrossReferenceDao($this);

        $this->rm->setReturnReference('_getReferenceDao', $ref_dao);
        $this->rm->setReturnReference('_getCrossReferenceDao', $cross_dao);

        $group_id = 101;
        $from = 'bug';
        $to = 'task';
        $ref_dao->expect('updateProjectReferenceShortName', array($group_id, $from, $to));
        $cross_dao->expect('updateTargetKeyword', array($from, $to, $group_id));
        $cross_dao->expect('updateSourceKeyword', array($from, $to, $group_id));

        $this->rm->updateProjectReferenceShortName($group_id, $from, $to);
    }

    public function testInsertReferencesConvertsToUTF8()
    {
        $html = 'g&=+}éàùœ';
        $encoded = htmlentities($html, ENT_IGNORE, "UTF-8");
        $decoded = html_entity_decode($encoded, ENT_IGNORE, "ISO-8859-15");

        $pre_encoding = mb_detect_encoding($decoded, 'UTF-8,ISO-8859-15');
        $this->assertEqual($pre_encoding, 'ISO-8859-15');

        $this->rm->insertReferences($decoded, 45);

        $post_encoding = mb_detect_encoding($decoded, 'UTF-8,ISO-8859-15');
        $this->assertEqual($post_encoding, 'UTF-8');
    }

    public function itInsertsLinkForReferences()
    {
        $reference_dao                = \Mockery::mock(ReferenceDao::class);
        $data_access_result_reference = \Mockery::mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface::class);
        $data_access_result_reference->shouldReceive('getRow')->andReturns(
            [
                'id' => 1,
                'keyword' => 'myref',
                'description' => 'description',
                'link' => '/link=$1',
                'scope' => 'P',
                'service_short_name' => '',
                'nature' => 'other',
                'is_active' => true,
                'group_id' => 102
            ],
            false
        );
        $reference_dao->shouldReceive('searchActiveByGroupID')->andReturns($data_access_result_reference);
        $reference_dao->shouldReceive('getSystemReferenceNatureByKeyword')->andReturnFalse();
        $this->rm->setReturnValue('_getReferenceDao', $reference_dao);

        $html = 'myref #123';
        $this->rm->insertReferences($html, 102);
        $this->assertEqual(
            $html,
            '<a href="http:///goto?key=myref&val=123&group_id=102" title="description" class="cross-reference">myref #123</a>'
        );

        $html = 'Text &#x27;myref #123&#x27; end text';
        $this->rm->insertReferences($html, 102);
        $this->assertEqual(
            $html,
            'Text &#x27;<a href="http:///goto?key=myref&val=123&group_id=102" title="description" class="cross-reference">myref #123</a>&#x27; end text'
        );
    }

    public function itInsertsLinkForMentionAtTheBeginningOfTheString()
    {
        stub($this->user_manager)->getUserByUserName('username')->returns(mock('PFUser'));

        $html = '@username';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, '<a href="/users/username">@username</a>');
    }

    public function itDoesNotInsertsLinkForUserThatDoNotExist()
    {
        stub($this->user_manager)->getUserByUserName('username')->returns(null);

        $html = '@username';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, '@username');
    }

    public function itInsertsLinkForMentionAtTheMiddleOfTheString()
    {
        stub($this->user_manager)->getUserByUserName('username')->returns(mock('PFUser'));

        $html = '/cc @username';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, '/cc <a href="/users/username">@username</a>');
    }

    public function itInsertsLinkForMentionWhenPointAtTheMiddle()
    {
        stub($this->user_manager)->getUserByUserName('user.name')->returns(mock('PFUser'));

        $html = '/cc @user.name';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, '/cc <a href="/users/user.name">@user.name</a>');
    }

    public function itInsertsLinkForMentionWhenHyphenAtTheMiddle()
    {
        stub($this->user_manager)->getUserByUserName('user-name')->returns(mock('PFUser'));

        $html = '/cc @user-name';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, '/cc <a href="/users/user-name">@user-name</a>');
    }

    public function itInsertsLinkForMentionWhenUnderscoreAtTheMiddle()
    {
        stub($this->user_manager)->getUserByUserName('user_name')->returns(mock('PFUser'));

        $html = '/cc @user_name';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, '/cc <a href="/users/user_name">@user_name</a>');
    }

    public function itDoesNotInsertsLinkIfInvalidCaracterAtBegining()
    {
        stub($this->user_manager)->getUserByUserName('1username')->returns(mock('PFUser'));

        $html = '@1username';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, '@1username');
    }

    public function itDoesNotBreakEmailAddress()
    {
        $html = 'toto@userna.me';
        $this->rm->insertReferences($html, 0);
        $this->assertEqual($html, 'toto@userna.me');
    }
}
