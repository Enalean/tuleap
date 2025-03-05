<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Tuleap\Docman\Tests\Stub\TableFactoryForFileBuilderStub;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\Docman\Version\VersionDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionRepresentationCollectionBuilderTest extends TestCase
{
    private const USER_ID = 101;

    private VersionDao|\PHPUnit\Framework\MockObject\MockObject $docman_version_dao;
    private CoAuthorDao|\PHPUnit\Framework\MockObject\MockObject $co_author_dao;
    private VersionRepresentationCollectionBuilder $builder;
    private \PHPUnit\Framework\MockObject\MockObject|\Docman_ApprovalTableFileFactory $factory;

    protected function setUp(): void
    {
        $this->docman_version_dao = $this->createMock(VersionDao::class);
        $this->co_author_dao      = $this->createMock(CoAuthorDao::class);

        $this->factory = $this->createMock(\Docman_ApprovalTableFileFactory::class);

        $user        = UserTestBuilder::aUser()->withId(self::USER_ID)->build();
        $co_author_1 = UserTestBuilder::aUser()->withId(102)->build();
        $co_author_2 = UserTestBuilder::aUser()->withId(103)->build();

        $this->builder = new VersionRepresentationCollectionBuilder(
            $this->docman_version_dao,
            $this->co_author_dao,
            RetrieveUserByIdStub::withUsers($user, $co_author_1, $co_author_2),
            TableFactoryForFileBuilderStub::buildWithFactory($this->factory),
            ProvideUserAvatarUrlStub::build(),
        );
        $user_helper   = $this->createStub(\UserHelper::class);
        $user_helper->method('getUserUrl')->willReturn('/path/to/user');
        $user_helper->method('getDisplayNameFromUser')->willReturn('John Does');
        \UserHelper::setInstance($user_helper);
    }

    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testItBuildAVersionsRepresentation(): void
    {
        $dar_item = [
            'item_id' => 4,
            'title' => 'item',
            'user_id' => self::USER_ID,
            'update_date' => 1542099693,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
            'parent_id' => 100,
            'group_id' => 10,
        ];
        $item     = new \Docman_File($dar_item);

        $dar = [
            'id' => 234,
            'item_id' => 4,
            'number' => 1,
            'label' => 'my version label',
            'filename' => 'a_file.txt',
            'changelog' => '',
            'user_id' => self::USER_ID,
            'date' => 1542099693,
            'authoring_tool' => 'Awesome Office Editor',
        ];
        $this->docman_version_dao->method('searchByItemId')->willReturn([$dar]);
        $this->docman_version_dao->method('countByItemId')->willReturn(1);

        $this->co_author_dao->method('searchByVersionId')->willReturn([
            ['version_id' => 234, 'user_id' => 102],
            ['version_id' => 234, 'user_id' => 103],
        ]);

        $this->factory->method('getTableFromVersion')->willReturn(null);

        $collection = $this->builder->buildVersionsCollection($item, 50, 0);

        self::assertEquals(1, $collection->getTotalSize());
        $representation = $collection->getPaginatedFileversionrepresentations()[0];
        self::assertEquals(234, $representation->id);
        self::assertEquals(1, $representation->number);
        self::assertEquals('my version label', $representation->name);
        self::assertEquals('a_file.txt', $representation->filename);
        self::assertEquals('', $representation->changelog);
        self::assertNull($representation->approval_href);
        self::assertEquals('Awesome Office Editor', $representation->authoring_tool);
        self::assertCount(2, $representation->coauthors);
    }

    public function testItBuildAVersionsRepresentationWithApprovalTable(): void
    {
        $dar_item = [
            'item_id' => 4,
            'title' => 'item',
            'user_id' => self::USER_ID,
            'update_date' => 1542099693,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
            'parent_id' => 100,
            'group_id' => 10,
        ];
        $item     = new \Docman_File($dar_item);

        $dar = [
            'id' => 234,
            'item_id' => 4,
            'number' => 1,
            'label' => 'my version label',
            'filename' => 'a_file.txt',
            'changelog' => '',
            'user_id' => self::USER_ID,
            'date' => 1542099693,
        ];
        $this->docman_version_dao->method('searchByItemId')->willReturn([$dar]);
        $this->docman_version_dao->method('countByItemId')->willReturn(1);

        $this->co_author_dao->method('searchByVersionId')->willReturn([]);

        $this->factory->method('getTableFromVersion')->willReturn($this->createMock(\Docman_ApprovalTable::class));

        $collection = $this->builder->buildVersionsCollection($item, 50, 0);

        self::assertEquals(1, $collection->getTotalSize());
        $representation = $collection->getPaginatedFileversionrepresentations()[0];
        self::assertEquals(
            '/plugins/docman/?group_id=10&action=details&section=approval&id=4&version=1',
            $representation->approval_href
        );
    }
}
