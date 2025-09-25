<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Links;

use Tuleap\Docman\Version\LinkVersionDao;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class VersionRepresentationCollectionBuilderTest extends TestCase
{
    private LinkVersionDao|\PHPUnit\Framework\MockObject\MockObject $dao;
    private VersionRepresentationCollectionBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = $this->createMock(LinkVersionDao::class);

        $this->builder = new VersionRepresentationCollectionBuilder(
            $this->dao,
            RetrieveUserByIdStub::withUser(UserTestBuilder::buildWithDefaults()),
            ProvideUserAvatarUrlStub::build(),
        );
        $user_helper   = $this->createStub(\UserHelper::class);
        $user_helper->method('getUserUrl')->willReturn('/path/to/user');
        $user_helper->method('getDisplayNameFromUser')->willReturn('John Doe');
        \UserHelper::setInstance($user_helper);
    }

    #[\Override]
    protected function tearDown(): void
    {
        \UserHelper::clearInstance();
    }

    public function testItBuildAVersionsRepresentation(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $item = new \Docman_Link([
            'item_id'     => 4,
            'title'       => 'item',
            'user_id'     => (int) $user->getId(),
            'update_date' => 1542099693,
            'item_type'   => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            'parent_id'   => 100,
            'group_id'    => 10,
            'link_url'    => 'https://example.com',
        ]);

        $this->dao
            ->method('searchByItemId')
            ->willReturn([[
                'id'        => 234,
                'item_id'   => 4,
                'number'    => 1,
                'label'     => 'my version label',
                'changelog' => '',
                'user_id'   => (int) $user->getId(),
                'date'      => 1542099693,
                'link_url'  => 'https://example.com',
            ],
            ]);
        $this->dao->method('countByItemId')->willReturn(1);

        $collection = $this->builder->buildVersionsCollection($item, 50, 0);

        self::assertEquals(1, $collection->getTotalSize());
        $representation = $collection->getRepresentations()[0];
        self::assertEquals(234, $representation->id);
        self::assertEquals(1, $representation->number);
        self::assertEquals('my version label', $representation->name);
        self::assertEquals('', $representation->changelog);
    }
}
