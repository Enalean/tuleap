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
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use ReferenceDao;
use ReferenceManager;
use TestHelper;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

final class ReferenceManagerTest extends TestCase
{
    use GlobalLanguageMock;

    private ReferenceManager&MockObject $rm;
    private UserManager&MockObject $user_manager;
    private ProjectManager&MockObject $project_manager;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);

        $event_manager = $this->createMock(EventManager::class);
        $event_manager->method('dispatch');
        $event_manager->method('processEvent');
        EventManager::setInstance($event_manager);
        ProjectManager::setInstance($this->project_manager);
        $this->user_manager = $this->createMock(UserManager::class);
        UserManager::setInstance($this->user_manager);

        $this->rm = $this->getMockBuilder(ReferenceManager::class)
            ->setConstructorArgs([])
            ->onlyMethods([
                '_getReferenceDao',
                '_getCrossReferenceDao',
                'getGroupIdFromArtifactIdForCallbackFunction',
                'getGroupIdFromArtifactId',
            ])
            ->getMock();
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();
        ProjectManager::clearInstance();
        UserManager::clearInstance();
    }

    public function testSingleton(): void
    {
        self::assertInstanceOf(ReferenceManager::class, ReferenceManager::instance());
        self::assertSame(ReferenceManager::instance(), ReferenceManager::instance());
    }

    public function testExtractReference(): void
    {
        $GLOBALS['Language']->method('getOverridableText')->willReturn('some text');

        $dao = $this->createMock(ReferenceDao::class);
        $dao->method('searchActiveByGroupID')
            ->withConsecutive(['100'], ['1'])
            ->willReturnOnConsecutiveCalls(
                TestHelper::arrayToDar([
                    'id'                 => 1,
                    'keyword'            => 'art',
                    'description'        => 'reference_art_desc_key',
                    'link'               => '/tracker/?func=detail&aid=$1&group_id=$group_id',
                    'scope'              => 'S',
                    'service_short_name' => 'tracker',
                    'nature'             => 'artifact',
                    'reference_id'       => 1,
                    'group_id'           => 100,
                    'is_active'          => 1,
                ]),
                TestHelper::arrayToDar(
                    [
                        'id'                 => 1,
                        'keyword'            => 'art',
                        'description'        => 'reference_art_desc_key',
                        'link'               => '/tracker/?func=detail&aid=$1&group_id=$group_id',
                        'scope'              => 'S',
                        'service_short_name' => 'tracker',
                        'nature'             => 'artifact',
                        'reference_id'       => 1,
                        'group_id'           => 1,
                        'is_active'          => 1,
                    ]
                )
            );
        $dao->method('getSystemReferenceNatureByKeyword');

        //The Reference manager
        $this->rm->method('_getReferenceDao')->willReturn($dao);
        $this->rm->method('getGroupIdFromArtifactIdForCallbackFunction')->willReturn('100', '1', '100');
        $this->rm->method('getGroupIdFromArtifactId');

        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        self::assertCount(1, $this->rm->extractReferences('art #123', 0), 'Art is a shared keyword for all projects');
        self::assertCount(0, $this->rm->extractReferences('arto #123', 0), 'Should not extract a reference on unknown keyword');
        self::assertCount(1, $this->rm->extractReferences('art #1:123', 0), 'Art is a reference for project num 1');
        self::assertCount(1, $this->rm->extractReferences('art #100:123', 0), 'Art is a reference for project named codendi');
    }

    public function testExtractRegexp(): void
    {
        $this->rm->method('_getReferenceDao')->willReturn($this->createMock(ReferenceDao::class));

        self::assertCount(0, $this->rm->_extractAllMatches('art 123'), 'No sharp sign');
        self::assertCount(0, $this->rm->_extractAllMatches('art#123'), 'No space');
        self::assertCount(0, $this->rm->_extractAllMatches('art #'), 'No reference');

        self::assertCount(1, $this->rm->_extractAllMatches('art #123'), 'simple reference');
        self::assertCount(1, $this->rm->_extractAllMatches('art #abc'), 'No number');
        self::assertCount(1, $this->rm->_extractAllMatches('art #abc:123'), 'groupName:ObjID');
        self::assertCount(1, $this->rm->_extractAllMatches('art #123:123'), 'groupID:ObjID');
        self::assertCount(1, $this->rm->_extractAllMatches('art #abc:abc'), 'groupName:ObjName');
        self::assertCount(1, $this->rm->_extractAllMatches('art #123:abc'), 'groupID:ObjName');
        self::assertCount(4, $this->rm->_extractAllMatches('art #123:abc is a reference to art #123 and rev #codendi:123 as well as file #123:release1'), 'Multiple extracts');
        self::assertCount(2, $this->rm->_extractAllMatches('art #123-rev #123'), "Multiple extracts with '-'");
        self::assertCount(1, $this->rm->_extractAllMatches('art #123:wikipage/2'), 'Wikipage revision number');

        // Projectname with - and _ See SR #1178
        $matches = $this->rm->_extractAllMatches('art #abc-def:ghi');
        self::assertEquals('abc-def:', $matches[0]['project_name']);
        self::assertEquals('ghi', $matches[0]['value']);
        $matches = $this->rm->_extractAllMatches('art #abc-de_f:ghi');
        self::assertEquals('abc-de_f:', $matches[0]['project_name']);
        self::assertEquals('ghi', $matches[0]['value']);

        // SR #2353 - Reference to wiki page name with "&" does not work
        $matches = $this->rm->_extractAllMatches('wiki #project:page/subpage&amp;toto&tutu & co');
        self::assertEquals('wiki', $matches[0]['key']);
        self::assertEquals('project:', $matches[0]['project_name']);
        self::assertEquals('page/subpage&amp;toto&tutu', $matches[0]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &#039;ref #12784&#039; into stable/master');
        self::assertCount(1, $matches);
        self::assertEquals('ref', $matches[0]['key']);
        self::assertEquals('12784', $matches[0]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &#039;ref #12784&#039; for doc #123');
        self::assertCount(2, $matches);
        self::assertEquals('ref', $matches[0]['key']);
        self::assertEquals('12784', $matches[0]['value']);
        self::assertEquals('doc', $matches[1]['key']);
        self::assertEquals('123', $matches[1]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &#x27;ref #12784&#x27; for doc #123');
        self::assertCount(2, $matches);
        self::assertEquals('ref', $matches[0]['key']);
        self::assertEquals('12784', $matches[0]['value']);
        self::assertEquals('doc', $matches[1]['key']);
        self::assertEquals('123', $matches[1]['value']);

        $matches = $this->rm->_extractAllMatches('Merge &quot;ref #12784&quot; for doc #123');
        self::assertCount(2, $matches);
        self::assertEquals('ref', $matches[0]['key']);
        self::assertEquals('12784', $matches[0]['value']);
        self::assertEquals('doc', $matches[1]['key']);
        self::assertEquals('123', $matches[1]['value']);

        $matches = $this->rm->_extractAllMatches('See ref #12784.');
        self::assertCount(1, $matches);
        self::assertEquals('ref', $matches[0]['key']);
        self::assertEquals('12784', $matches[0]['value']);

        $matches = $this->rm->_extractAllMatches('See ref #a.b-c_d/12784.');
        self::assertCount(1, $matches);
        self::assertEquals('ref', $matches[0]['key']);
        self::assertEquals('a.b-c_d/12784', $matches[0]['value']);
    }

    public function testUpdateProjectReferenceShortName(): void
    {
        $ref_dao   = $this->createMock(ReferenceDao::class);
        $cross_dao = $this->createMock(CrossReferencesDao::class);

        $this->rm->method('_getReferenceDao')->willReturn($ref_dao);
        $this->rm->method('_getCrossReferenceDao')->willReturn($cross_dao);

        $group_id = 101;
        $from     = 'bug';
        $to       = 'task';
        $ref_dao->expects(self::once())->method('updateProjectReferenceShortName')->with($group_id, $from, $to);
        $cross_dao->expects(self::once())->method('updateTargetKeyword')->with($from, $to, $group_id);
        $cross_dao->expects(self::once())->method('updateSourceKeyword')->with($from, $to, $group_id);

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
        $reference_dao                = $this->createMock(ReferenceDao::class);
        $data_access_result_reference = $this->createStub(LegacyDataAccessResultInterface::class);
        $data_access_result_reference->method('getRow')->willReturn(
            [
                'id'                 => 1,
                'keyword'            => 'myref',
                'description'        => 'description',
                'link'               => '/link=$1',
                'scope'              => 'P',
                'service_short_name' => '',
                'nature'             => 'other',
                'is_active'          => true,
                'group_id'           => 102,
            ],
            false
        );
        $reference_dao->method('searchActiveByGroupID')->willReturn($data_access_result_reference);
        $reference_dao->method('getSystemReferenceNatureByKeyword')->willReturn(false);
        $this->rm->method('_getReferenceDao')->willReturn($reference_dao);

        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $html = 'myref #123';
        $this->rm->insertReferences($html, 102);
        self::assertEquals(
            '<a href="https:///goto?key=myref&val=123&group_id=102" title="description" class="cross-reference">myref #123</a>',
            $html
        );

        $html = 'Text &#x27;myref #123&#x27; end text';
        $this->rm->insertReferences($html, 102);
        self::assertEquals(
            'Text &#x27;<a href="https:///goto?key=myref&val=123&group_id=102" title="description" class="cross-reference">myref #123</a>&#x27; end text',
            $html
        );
    }

    public function testItLeavesReferencesThatDontMatchAnythingIntact(): void
    {
        $reference_dao = $this->createStub(ReferenceDao::class);
        $reference_dao->method('searchActiveByGroupID')->willReturn(TestHelper::emptyDar());
        $reference_dao->method('getSystemReferenceNatureByKeyword')->willReturn(false);
        $this->rm->method('_getReferenceDao')->willReturn($reference_dao);

        $this->project_manager->method('getProject')->willReturn(
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
        $this->user_manager->method('getUserByUserName')->with('username')->willReturn(UserTestBuilder::buildWithDefaults());

        $html = '@username';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('<a href="/users/username" class="direct-link-to-user">@username</a>', $html);
    }

    public function testItDoesNotInsertsLinkForUserThatDoNotExist(): void
    {
        $this->user_manager->method('getUserByUserName')->with('username')->willReturn(null);

        $html = '@username';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('@username', $html);
    }

    public function testItInsertsLinkForMentionAtTheMiddleOfTheString(): void
    {
        $this->user_manager->method('getUserByUserName')->with('username')->willReturn(UserTestBuilder::buildWithDefaults());

        $html = '/cc @username';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('/cc <a href="/users/username" class="direct-link-to-user">@username</a>', $html);
    }

    public function testItInsertsLinkForMentionWhenPointAtTheMiddle(): void
    {
        $this->user_manager->method('getUserByUserName')->with('user.name')->willReturn(UserTestBuilder::buildWithDefaults());

        $html = '/cc @user.name';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('/cc <a href="/users/user.name" class="direct-link-to-user">@user.name</a>', $html);
    }

    public function testItInsertsLinkForMentionWhenHyphenAtTheMiddle(): void
    {
        $this->user_manager->method('getUserByUserName')->with('user-name')->willReturn(UserTestBuilder::buildWithDefaults());

        $html = '/cc @user-name';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('/cc <a href="/users/user-name" class="direct-link-to-user">@user-name</a>', $html);
    }

    public function testItInsertsLinkForMentionWhenUnderscoreAtTheMiddle(): void
    {
        $this->user_manager->method('getUserByUserName')->with('user_name')->willReturn(UserTestBuilder::buildWithDefaults());

        $html = '/cc @user_name';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('/cc <a href="/users/user_name" class="direct-link-to-user">@user_name</a>', $html);
    }

    public function testItDoesNotInsertsLinkIfInvalidCharacterAtBeginning(): void
    {
        $this->user_manager->method('getUserByUserName')->with('1username')->willReturn(UserTestBuilder::buildWithDefaults());

        $html = '@1username';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('@1username', $html);
    }

    public function testItDoesNotBreakEmailAddress(): void
    {
        $html = 'toto@userna.me';
        $this->rm->insertReferences($html, 0);
        self::assertEquals('toto@userna.me', $html);
    }
}
