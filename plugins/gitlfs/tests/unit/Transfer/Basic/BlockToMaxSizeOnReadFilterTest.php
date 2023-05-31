<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Transfer\Basic;

use Tuleap\GitLFS\StreamFilter\StreamFilter;

final class BlockToMaxSizeOnReadFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFilterBlocksOnceMaximumExpectedSizeIsReached(): void
    {
        $input_source = fopen('php://memory', 'rb+');

        $maximum_size = 1024;
        $block_filter = new BlockToMaxSizeOnReadFilter($maximum_size);
        StreamFilter::prependFilter($input_source, $block_filter);

        $input_data = \str_repeat('A', $maximum_size);
        fwrite($input_source, $input_data);
        rewind($input_source);

        self::assertSame($input_data, stream_get_contents($input_source));
        self::assertSame($maximum_size, $block_filter->getReadDataSize());
        self::assertFalse($block_filter->hasMaximumSizeBeenExceeded());

        rewind($input_source);
        self::assertSame('', stream_get_contents($input_source));
        self::assertSame($maximum_size, $block_filter->getReadDataSize());
        self::assertTrue($block_filter->hasMaximumSizeBeenExceeded());

        fclose($input_source);
    }

    public function testNegativeSizeIsRejected(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new BlockToMaxSizeOnReadFilter(-123);
    }
}
