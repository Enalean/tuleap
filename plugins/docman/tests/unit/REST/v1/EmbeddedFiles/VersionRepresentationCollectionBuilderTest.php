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

namespace Tuleap\Docman\REST\v1\EmbeddedFiles;

use Tuleap\Docman\Tests\Stub\TableFactoryForFileBuilderStub;
use Tuleap\Docman\Version\VersionDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionRepresentationCollectionBuilderTest extends TestCase
{
    private const PROJECT_ID = 10;

    private VersionDao|\PHPUnit\Framework\MockObject\MockObject $docman_version_dao;
    private VersionRepresentationCollectionBuilder $builder;
    private \PHPUnit\Framework\MockObject\MockObject|\Docman_ApprovalTableFileFactory $factory;

    protected function setUp(): void
    {
        $this->docman_version_dao = $this->createMock(VersionDao::class);

        $this->factory = $this->createMock(\Docman_ApprovalTableFileFactory::class);

        $this->builder = new VersionRepresentationCollectionBuilder(
            $this->docman_version_dao,
            RetrieveUserByIdStub::withUser(UserTestBuilder::buildWithDefaults()),
            TableFactoryForFileBuilderStub::buildWithFactory($this->factory),
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()),
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
        $user = UserTestBuilder::buildWithDefaults();

        $dar_item = [
            'item_id' => 4,
            'title' => 'item',
            'user_id' => (int) $user->getId(),
            'update_date' => 1542099693,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            'parent_id' => 100,
            'group_id' => 10,
        ];
        $item     = new \Docman_EmbeddedFile($dar_item);

        $dar = [
            'id' => 234,
            'item_id' => 4,
            'number' => 1,
            'label' => 'my version label',
            'filename' => 'file',
            'changelog' => '',
            'user_id' => (int) $user->getId(),
            'date' => 1542099693,
        ];
        $this->docman_version_dao->method('searchByItemId')->willReturn([$dar]);
        $this->docman_version_dao->method('countByItemId')->willReturn(1);

        $this->factory->method('getTableFromVersion')->willReturn(null);

        $collection = $this->builder->buildVersionsCollection($item, 50, 0);

        self::assertEquals(1, $collection->getTotalSize());
        $representation = $collection->getRepresentations()[0];
        self::assertEquals(234, $representation->id);
        self::assertEquals(1, $representation->number);
        self::assertEquals('my version label', $representation->name);
        self::assertEquals('', $representation->changelog);
        self::assertNull($representation->approval_href);
    }

    public function testItBuildAVersionsRepresentationWithApprovalTable(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $dar_item = [
            'item_id' => 4,
            'title' => 'item',
            'user_id' => (int) $user->getId(),
            'update_date' => 1542099693,
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            'parent_id' => 100,
            'group_id' => 10,
        ];
        $item     = new \Docman_EmbeddedFile($dar_item);

        $dar = [
            'id' => 234,
            'item_id' => 4,
            'number' => 1,
            'label' => 'my version label',
            'filename' => 'file',
            'changelog' => '',
            'user_id' => (int) $user->getId(),
            'date' => 1542099693,
        ];
        $this->docman_version_dao->method('searchByItemId')->willReturn([$dar]);
        $this->docman_version_dao->method('countByItemId')->willReturn(1);

        $this->factory->method('getTableFromVersion')->willReturn($this->createMock(\Docman_ApprovalTable::class));

        $collection = $this->builder->buildVersionsCollection($item, 50, 0);

        self::assertEquals(1, $collection->getTotalSize());
        $representation = $collection->getRepresentations()[0];
        self::assertEquals(
            '/plugins/docman/?group_id=10&action=details&section=approval&id=4&version=1',
            $representation->approval_href
        );
    }
}
