<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class LargestObjectSizeGitRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExpectedValuesAreRetrieved(): void
    {
        $repository = \Mockery::mock(\GitRepository::class);
        $size       = 12;

        $repository_with_largest_object_size = new LargestObjectSizeGitRepository($repository, $size);

        $this->assertSame($repository, $repository_with_largest_object_size->getRepository());
        $this->assertSame($size, $repository_with_largest_object_size->getLargestObjectSize());
    }

    /**
     * @dataProvider providerObjectSize
     */
    public function testVerifyIfARepositoryIsOverTheLimit(int $size, bool $is_over_the_limit): void
    {
        $repository_with_largest_object_size = new LargestObjectSizeGitRepository(
            \Mockery::mock(\GitRepository::class),
            $size
        );

        $this->assertSame($is_over_the_limit, $repository_with_largest_object_size->isOverTheObjectSizeLimit());
    }

    public function providerObjectSize(): array
    {
        return [
            [0, false],
            [PHP_INT_MAX, true]
        ];
    }
}
