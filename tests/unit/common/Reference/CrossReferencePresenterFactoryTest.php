<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

final class CrossReferencePresenterFactoryTest extends TestCase
{
    use ForgeConfigSandbox;

    private CrossReferencesDao&MockObject $dao;
    private CrossReferencePresenterFactory $factory;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(CrossReferencesDao::class);

        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->factory = new CrossReferencePresenterFactory($this->dao);
    }

    public function testGetTargetsOfEntity(): void
    {
        $this->dao->method('searchTargetsOfEntity')
            ->with("PageName", "wiki", 102)
            ->willReturn(
                [
                    [
                        'id'             => 1,
                        'target_id'      => '123',
                        'target_gid'     => '103',
                        'target_type'    => 'tracker',
                        'target_keyword' => 'art',
                        'source_id'      => 'PageName',
                        'source_gid'     => '102',
                        'source_type'    => 'wiki',
                        'source_keyword' => 'wiki',
                    ],
                    [
                        'id'             => 2,
                        'target_id'      => '456',
                        'target_gid'     => '100',
                        'target_type'    => 'tracker',
                        'target_keyword' => 'art',
                        'source_id'      => 'PageName',
                        'source_gid'     => '102',
                        'source_type'    => 'wiki',
                        'source_keyword' => 'wiki',
                    ],
                ]
            );

        $presenters = $this->factory->getTargetsOfEntity("PageName", "wiki", 102);

        self::assertEquals(1, $presenters[0]->id);
        self::assertEquals('https://example.com/goto?key=art&val=123&group_id=103', $presenters[0]->url);
        self::assertEquals(
            '/reference/rmreference.php?target_id=123&target_gid=103&target_type=tracker&target_key=art&source_id=PageName&source_gid=102&source_type=wiki&source_key=wiki',
            $presenters[0]->delete_url
        );
        self::assertEquals(2, $presenters[1]->id);
        self::assertEquals(
            'https://example.com/goto?key=art&val=456',
            $presenters[1]->url
        );
        self::assertEquals(
            '/reference/rmreference.php?target_id=456&target_gid=100&target_type=tracker&target_key=art&source_id=PageName&source_gid=102&source_type=wiki&source_key=wiki',
            $presenters[1]->delete_url
        );
    }

    public function testGetSourcesOfEntity(): void
    {
        $this->dao->method('searchSourcesOfEntity')
            ->with("PageName", "wiki", 102)
            ->willReturn(
                [
                    [
                        'id'             => 1,
                        'target_id'      => 'PageName',
                        'target_gid'     => '102',
                        'target_type'    => 'wiki',
                        'target_keyword' => 'wiki',
                        'source_id'      => '123',
                        'source_gid'     => '103',
                        'source_type'    => 'tracker',
                        'source_keyword' => 'art',
                    ],
                    [
                        'id'             => 2,
                        'target_id'      => 'PageName',
                        'target_gid'     => '102',
                        'target_type'    => 'wiki',
                        'target_keyword' => 'wiki',
                        'source_id'      => '456',
                        'source_gid'     => '100',
                        'source_type'    => 'tracker',
                        'source_keyword' => 'art',
                    ],
                ]
            );

        $presenters = $this->factory->getSourcesOfEntity("PageName", "wiki", 102);

        self::assertEquals(1, $presenters[0]->id);
        self::assertEquals('https://example.com/goto?key=art&val=123&group_id=103', $presenters[0]->url);
        self::assertEquals(
            '/reference/rmreference.php?target_id=PageName&target_gid=102&target_type=wiki&target_key=wiki&source_id=123&source_gid=103&source_type=tracker&source_key=art',
            $presenters[0]->delete_url
        );
        self::assertEquals(2, $presenters[1]->id);
        self::assertEquals(
            'https://example.com/goto?key=art&val=456',
            $presenters[1]->url
        );
        self::assertEquals(
            '/reference/rmreference.php?target_id=PageName&target_gid=102&target_type=wiki&target_key=wiki&source_id=456&source_gid=100&source_type=tracker&source_key=art',
            $presenters[1]->delete_url
        );
    }
}
