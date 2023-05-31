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

namespace Tuleap\GitLFS\StreamFilter;

final class StreamFilterWrapperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testIncorrectFilterIsRejected(): void
    {
        $filter_wrapper         = new StreamFilterWrapper();
        $filter_wrapper->params = 'invalid_filter';

        $this->expectException(\InvalidArgumentException::class);

        $filter_wrapper->onCreate();
    }

    public function testTheCorrectAmountOfWrittenDataIsCounted(): void
    {
        $filter = new class implements FilterInterface {
            /**
             * @param string $data_chunk
             */
            public function process($data_chunk): string
            {
                return $data_chunk;
            }

            public function getFilteredChainIdentifier(): int
            {
                return STREAM_FILTER_WRITE;
            }

            public function filterDetachedEvent(): void
            {
            }
        };

        $destination_resource = fopen('php://memory', 'wb');
        StreamFilter::prependFilter($destination_resource, $filter);

        $content           = 'Test data';
        $written_data_size = fwrite($destination_resource, $content);

        self::assertSame(strlen($content), $written_data_size);

        fclose($destination_resource);
    }
}
