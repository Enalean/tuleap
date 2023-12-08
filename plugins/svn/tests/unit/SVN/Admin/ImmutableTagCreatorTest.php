<?php
/**
 * Copyright (c) Enalean, 2021-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace Tuleap\SVN\Admin;

use ProjectHistoryDao;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ImmutableTagCreatorTest extends TestCase
{
    private ImmutableTagCreator $immutable_tag_creator;

    protected function setUp(): void
    {
        $this->immutable_tag_creator = new ImmutableTagCreator(
            $this->createStub(ImmutableTagDao::class),
            $this->createStub(ProjectHistoryFormatter::class),
            $this->createStub(ProjectHistoryDao::class),
            $this->createStub(ImmutableTagFactory::class)
        );
    }

    public function testVeryLargeListOfImmutablePathsIsRejected(): void
    {
        $large_immutable_paths = '/' . str_repeat('a', 200_000);

        $this->expectException(ImmutableTagListTooBigException::class);
        $this->immutable_tag_creator->saveWithoutHistory(self::buildRepository(), $large_immutable_paths, '');
    }

    public function testVeryLargeAllowListOfImmutableTagsIsRejected(): void
    {
        $large_allowlist = '/' . str_repeat('a', 200_000);

        $this->expectException(ImmutableTagListTooBigException::class);
        $this->immutable_tag_creator->saveWithoutHistory(self::buildRepository(), '/b', $large_allowlist);
    }

    private static function buildRepository(): Repository
    {
        return SvnRepository::buildActiveRepository(1, 'name', ProjectTestBuilder::aProject()->build());
    }
}
