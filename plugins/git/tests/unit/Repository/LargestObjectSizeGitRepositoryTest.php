<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository;

use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LargestObjectSizeGitRepositoryTest extends TestCase
{
    public function testExpectedValuesAreRetrieved(): void
    {
        $repository = GitRepositoryTestBuilder::aProjectRepository()->build();
        $size       = 12;

        $repository_with_largest_object_size = new LargestObjectSizeGitRepository($repository, $size);

        self::assertSame($repository, $repository_with_largest_object_size->getRepository());
        self::assertSame($size, $repository_with_largest_object_size->getLargestObjectSize());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('providerObjectSize')]
    public function testVerifyIfARepositoryIsOverTheLimit(int $size, bool $is_over_the_limit): void
    {
        $repository_with_largest_object_size = new LargestObjectSizeGitRepository(
            GitRepositoryTestBuilder::aProjectRepository()->build(),
            $size
        );

        self::assertSame($is_over_the_limit, $repository_with_largest_object_size->isOverTheObjectSizeLimit());
    }

    public static function providerObjectSize(): array
    {
        return [
            [0, false],
            [PHP_INT_MAX, true],
        ];
    }
}
