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

namespace Tuleap\Reference;

use EventManager;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectManager;
use ReferenceManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use UserManager;

final class ReferenceManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ReferenceManager
     */
    private $rm;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        $this->project_manager = \Mockery::mock(ProjectManager::class);

        EventManager::setInstance(\Mockery::spy(\EventManager::class));
        ProjectManager::setInstance($this->project_manager);
        $this->user_manager = \Mockery::spy(UserManager::class);
        UserManager::setInstance($this->user_manager);

        $this->rm = \Mockery::spy(
            ReferenceManager::class . '[' . implode(',', [
                '_getReferenceDao',
                '_getCrossReferenceDao',
                'loadReservedKeywords',
                'getGroupIdFromArtifactIdForCallbackFunction',
                'getGroupIdFromArtifactId',
            ]) . ']',
            []
        )->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        ProjectManager::clearInstance();
        UserManager::clearInstance();
    }

    public function testSingleton(): void
    {
        $this->assertInstanceOf(\ReferenceManager::class, ReferenceManager::instance());
        $this->assertSame(ReferenceManager::instance(), ReferenceManager::instance());
    }

    public function testExtractReference(): void
    {
        $GLOBALS['Language']->method('getOverridableText')->willReturn('some text');

        $dao = \Mockery::spy(\ReferenceDao::class);
        $dao->shouldReceive('searchActiveByGroupID')->with('100')
            ->andReturn(\TestHelper::arrayToDar(
                [
                    'id' => 1,
                    'keyword' => 'art',
                    'description' => 'reference_art_desc_key',
                    'link' => '/tracker/?func=detail&aid=$1&group_id=$group_id',
                    'scope' => 'S',
                    'service_short_name' => 'tracker',
                    'nature' => 'artifact',
                    'reference_id' => 1,
                    'group_id' => 100,
                    'is_active' => 1,
                ]
            ));
        $dao->shouldReceive('searchActiveByGroupID')->with('1')
            ->andReturn(\TestHelper::arrayToDar(
                [
                    'id' => 1,
                    'keyword' => 'art',
                    'description' => 'reference_art_desc_key',
                    'link' => '/tracker/?func=detail&aid=$1&group_id=$group_id',
                    'scope' => 'S',
                    'service_short_name' => 'tracker',
                    'nature' => 'artifact',
                    'reference_id' => 1,
                    'group_id' => 1,
                    'is_active' => 1,
                ]
            ));

        //The Reference manager
        $this->rm->shouldReceive('_getReferenceDao')->andReturn($dao);
        $this->rm->shouldReceive('getGroupIdFromArtifactIdForCallbackFunction')->andReturn('100', '1', '100');

        $this->project_manager->shouldReceive('getProject')->andReturn(\Mockery::mock(\Project::class));

        $this->assertCount(1, $this->rm->extractReferences('art #123', 0), 'Art is a shared keyword for all projects');
        $this->assertCount(0, $this->rm->extractReferences('arto #123', 0), 'Should not extract a reference on unknown keyword');
        $this->assertCount(1, $this->rm->extractReferences('art #1:123', 0), 'Art is a reference for project num 1');
        $this->assertCount(1, $this->rm->extractReferences('art #100:123', 0), 'Art is a reference for project named codendi');
    }

    public function testExtractRegexp(): void
    {
        $this->rm->shouldReceive('_getReferenceDao', \Mockery::spy(\ReferenceDao::class));

        $this->assertCount(0, $this->rm->_extractAllMatches('art 123'), 'No sharp sign');
        $this->assertCount(0, $this->rm->_extractAllMatches('art#123'), 'No space');
        $this->assertCount(0, $this->rm->_extractAllMatches('art #'), 'No reference');

        $this->assertCount(1, $this->rm->_extractAllMatches('art #123'), 'simple reference');
        $this->assertCount(1, $this->rm->_extractAllMatches('art #abc'), 'No number');
        $this->assertCount(1, $this->rm->_extractAllMatches('art #abc:123'), 'groupName:ObjID');
        $this->assertCount(1, $this->rm->_extractAllMatches('art #123:123'), 'groupID:ObjID');
        $this->assertCount(1, $this->rm->_extractAllMatches('art #abc:abc'), 'groupName:ObjName');
        $this->assertCount(1, $this->rm->_extractAllMatches('art #123:abc'), 'groupID:ObjName');
        $this->assertCount(4, $this->rm->_extractAllMatches('art #123:abc is a reference to art #123 and rev #codendi:123 as well as file #123:release1'), 'Multiple extracts');
        $this->assertCount(2, $this->rm->_extractAllMatches('art #123-rev #123'), "Multiple extracts with '-'");
        $this->assertCount(1, $this->rm->_extractAllMatches('art #123:wikipage/2'), 'Wikipage revision number');

        // Projectname with - and _ See SR #1178
        $matches = $this->rm->_extractAllMatches('art #abc-def:ghi');
        $this->assertEquals('abc-def:', $matches[0]['project_name']);
        $this->assertEquals('ghi', $matches[0]['value']);
        $matches = $this->rm->_extractAllMatches('art #abc-de_f:ghi');
        $this->assertEquals('abc-de_f:', $matches[0]['project_name']);
        $this->assertEquals('ghi', $matches[0]['value']);

        // SR #2353 - Reference to wiki page name with "&" does not work
        $matches = $this->rm->_extractAllMatches('wiki #project:page/subpage&amp;toto&tutu & co');
        $this->assertEquals('wiki', $matches[0]['key']);
        $this->assertEquals('project:', $matches[0]['project_name']);
        $this->assertEquals('page/subpage&amp;toto&tutu', $matches[0]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &#039;ref #12784&#039; into stable/master');
        $this->assertCount(1, $matches);
        $this->assertEquals('ref', $matches[0]['key']);
        $this->assertEquals('12784', $matches[0]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &#039;ref #12784&#039; for doc #123');
        $this->assertCount(2, $matches);
        $this->assertEquals('ref', $matches[0]['key']);
        $this->assertEquals('12784', $matches[0]['value']);
        $this->assertEquals('doc', $matches[1]['key']);
        $this->assertEquals('123', $matches[1]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &#x27;ref #12784&#x27; for doc #123');
        $this->assertCount(2, $matches);
        $this->assertEquals('ref', $matches[0]['key']);
        $this->assertEquals('12784', $matches[0]['value']);
        $this->assertEquals('doc', $matches[1]['key']);
        $this->assertEquals('123', $matches[1]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &quot;ref #12784&quot; for doc #123');
        $this->assertCount(2, $matches);
        $this->assertEquals('ref', $matches[0]['key']);
        $this->assertEquals('12784', $matches[0]['value']);
        $this->assertEquals('doc', $matches[1]['key']);
        $this->assertEquals('123', $matches[1]['value']);

        $matches = $this->rm->_extractAllMatches('See ref #12784.');
        $this->assertCount(1, $matches);
        $this->assertEquals('ref', $matches[0]['key']);
        $this->assertEquals('12784', $matches[0]['value']);

        $matches = $this->rm->_extractAllMatches('See ref #a.b-c_d/12784.');
        $this->assertCount(1, $matches);
        $this->assertEquals('ref', $matches[0]['key']);
        $this->assertEquals('a.b-c_d/12784', $matches[0]['value']);
    }

    public function testUpdateProjectReferenceShortName(): void
    {
        $ref_dao   = \Mockery::spy(\ReferenceDao::class);
        $cross_dao = \Mockery::spy(CrossReferenceDao::class);

        $this->rm->shouldReceive('_getReferenceDao')->andReturn($ref_dao);
        $this->rm->shouldReceive('_getCrossReferenceDao')->andReturn($cross_dao);

        $group_id = 101;
        $from     = 'bug';
        $to       = 'task';
        $ref_dao->shouldReceive('updateProjectReferenceShortName')->with($group_id, $from, $to)->once();
        $cross_dao->shouldReceive('updateTargetKeyword')->with($from, $to, $group_id)->once();
        $cross_dao->shouldReceive('updateSourceKeyword')->with($from, $to, $group_id)->once();

        $this->rm->updateProjectReferenceShortName($group_id, $from, $to);
    }

    public function testInsertReferencesPlayWellWithUTF8(): void
    {
        $initial_string = 'g&=+}éàùœ🐰';
        $html           = $initial_string;

        $this->rm->insertReferences($html, 45);

        self::assertEquals($initial_string, $html);
    }

    public function testItInsertsLinkForReferences(): void
    {
        $reference_dao                = \Mockery::mock(\ReferenceDao::class);
        $data_access_result_reference = $this->createStub(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface::class);
        $data_access_result_reference->method('getRow')->willReturn(
            [
                'id' => 1,
                'keyword' => 'myref',
                'description' => 'description',
                'link' => '/link=$1',
                'scope' => 'P',
                'service_short_name' => '',
                'nature' => 'other',
                'is_active' => true,
                'group_id' => 102,
            ],
            false
        );
        $reference_dao->shouldReceive('searchActiveByGroupID')->andReturns($data_access_result_reference);
        $reference_dao->shouldReceive('getSystemReferenceNatureByKeyword')->andReturnFalse();
        $this->rm->shouldReceive('_getReferenceDao')->andReturn($reference_dao);

        $this->project_manager->shouldReceive('getProject')->andReturn(\Mockery::mock(\Project::class));

        $html = 'myref #123';
        $this->rm->insertReferences($html, 102);
        $this->assertEquals(
            '<a href="https:///goto?key=myref&val=123&group_id=102" title="description" class="cross-reference">myref #123</a>',
            $html
        );

        $html = 'Text &#x27;myref #123&#x27; end text';
        $this->rm->insertReferences($html, 102);
        $this->assertEquals(
            'Text &#x27;<a href="https:///goto?key=myref&val=123&group_id=102" title="description" class="cross-reference">myref #123</a>&#x27; end text',
            $html
        );
    }

    public function testItLeavesReferencesThatDontMatchAnythingIntact(): void
    {
        $reference_dao = $this->createStub(\ReferenceDao::class);
        $reference_dao->method('searchActiveByGroupID')->willReturn(\TestHelper::emptyDar());
        $reference_dao->method('getSystemReferenceNatureByKeyword')->willReturn(false);
        $this->rm->shouldReceive('_getReferenceDao')->andReturn($reference_dao);

        $this->project_manager->shouldReceive('getProject')->andReturn(
            ProjectTestBuilder::aProject()->withId(102)->build()
        );

        $html = 'myref #123';
        $this->rm->insertReferences($html, 102);
        self::assertSame('myref #123', $html);

        $html = 'Text myref #123';
        $this->rm->insertReferences($html, 102);
        self::assertSame('Text myref #123', $html);
    }

    public function testItInsertsLinkForMentionAtTheBeginningOfTheString(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->with('username')->andReturn(\Mockery::spy(\PFUser::class));

        $html = '@username';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('<a href="/users/username" class="direct-link-to-user">@username</a>', $html);
    }

    public function testItDoesNotInsertsLinkForUserThatDoNotExist(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->with('username')->andReturn(null);

        $html = '@username';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('@username', $html);
    }

    public function testItInsertsLinkForMentionAtTheMiddleOfTheString(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->with('username')->andReturn(\Mockery::spy(\PFUser::class));

        $html = '/cc @username';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('/cc <a href="/users/username" class="direct-link-to-user">@username</a>', $html);
    }

    public function testItInsertsLinkForMentionWhenPointAtTheMiddle(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->with('user.name')->andReturn(\Mockery::spy(\PFUser::class));

        $html = '/cc @user.name';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('/cc <a href="/users/user.name" class="direct-link-to-user">@user.name</a>', $html);
    }

    public function testItInsertsLinkForMentionWhenHyphenAtTheMiddle(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->with('user-name')->andReturn(\Mockery::spy(\PFUser::class));

        $html = '/cc @user-name';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('/cc <a href="/users/user-name" class="direct-link-to-user">@user-name</a>', $html);
    }

    public function testItInsertsLinkForMentionWhenUnderscoreAtTheMiddle(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->with('user_name')->andReturn(\Mockery::spy(\PFUser::class));

        $html = '/cc @user_name';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('/cc <a href="/users/user_name" class="direct-link-to-user">@user_name</a>', $html);
    }

    public function testItDoesNotInsertsLinkIfInvalidCharacterAtBeginning(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->with('1username')->andReturn(\Mockery::spy(\PFUser::class));

        $html = '@1username';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('@1username', $html);
    }

    public function testItDoesNotBreakEmailAddress(): void
    {
        $html = 'toto@userna.me';
        $this->rm->insertReferences($html, 0);
        $this->assertEquals('toto@userna.me', $html);
    }
}
