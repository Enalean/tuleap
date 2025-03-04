<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchCommon\CLI;

use Symfony\Component\Console\Output\BufferedOutput;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgressQueueIndexItemCategorySymfonyOutputTest extends TestCase
{
    public function testOutputNothingWhenThereIsNoItem(): void
    {
        $output         = new BufferedOutput();
        $progress_queue = new ProgressQueueIndexItemCategorySymfonyOutput($output, 'category');

        [...$progress_queue->iterate([])];

        self::assertEmpty($output->fetch());
    }

    public function testMakeSureCategoryOfItemCurrentlyIndexedIsPresentInTheOutput(): void
    {
        $output            = new BufferedOutput();
        $expected_category = 'my_item_cat';
        $progress_queue    = new ProgressQueueIndexItemCategorySymfonyOutput($output, $expected_category);

        [...$progress_queue->iterate(['a'])];

        self::assertStringContainsString($expected_category, $output->fetch());
    }
}
