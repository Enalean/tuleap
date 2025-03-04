<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

require_once __DIR__ . '/ReplaceDataFilter.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StreamFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFilterIsAttachedToResource(): void
    {
        $source_resource = fopen('php://memory', 'rb+');
        fwrite($source_resource, '02e24c2314cb27f7b7c043345ca30c567c58e064');
        rewind($source_resource);

        $expected_data = 'Tuleap test case';
        StreamFilter::prependFilter($source_resource, new ReplaceDataFilter($expected_data));

        $destination_resource = fopen('php://memory', 'rb+');
        stream_copy_to_stream($source_resource, $destination_resource);
        fclose($source_resource);
        rewind($destination_resource);

        self::assertSame($expected_data, stream_get_contents($destination_resource));

        fclose($destination_resource);
    }

    public function testFilterCanBeDetachedFromAResource(): void
    {
        $resource      = fopen('php://memory', 'rb+');
        $expected_data = 'Tuleap test case without test case';
        fwrite($resource, $expected_data);
        rewind($resource);

        $expected_filtered_data = 'Tuleap test case filtered';
        $filter_handle          = StreamFilter::prependFilter(
            $resource,
            new ReplaceDataFilter($expected_filtered_data)
        );

        self::assertSame($expected_filtered_data, stream_get_contents($resource));
        StreamFilter::removeFilter($filter_handle);
        rewind($resource);
        self::assertSame($expected_data, stream_get_contents($resource));

        fclose($resource);
    }

    public function testAttachingAFilterToSomethingElseThanAResourceIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $not_a_resource = false;
        StreamFilter::prependFilter($not_a_resource, $this->createStub(FilterInterface::class));
    }

    public function testFilterWithInvalidChainIdentifierIsRejected(): void
    {
        $resource = fopen('php://memory', 'rb');
        $filter   = $this->createStub(FilterInterface::class);
        $filter->method('getFilteredChainIdentifier')->willReturn(123456789);

        $this->expectException(\DomainException::class);

        try {
            StreamFilter::prependFilter($resource, $filter);
        } finally {
            fclose($resource);
        }
    }

    public function testFilterThrowingAnExceptionDuringProcessingIsReplacedByFilterFatalError(): void
    {
        $source_resource = fopen('php://memory', 'rb+');
        fwrite($source_resource, 'Test data');
        rewind($source_resource);
        $filter = $this->createStub(FilterInterface::class);
        $filter->method('getFilteredChainIdentifier')->willReturn(STREAM_FILTER_READ);
        $filter->method('process')->willThrowException(new \Exception());
        $filter->method('filterDetachedEvent');

        StreamFilter::prependFilter($source_resource, $filter);

        $destination_resource = fopen('php://memory', 'wb');

        $this->assertFalse(stream_copy_to_stream($source_resource, $destination_resource));

        fclose($source_resource);
        fclose($destination_resource);
    }

    public function testUserFilterIsNotifiedWhenTheResourceIsBeingClosed(): void
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->method('getFilteredChainIdentifier')->willReturn(STREAM_FILTER_READ);
        $filter->expects(self::once())->method('filterDetachedEvent');

        $source_resource = fopen('php://memory', 'rb+');
        StreamFilter::prependFilter($source_resource, $filter);
        fclose($source_resource);
    }

    public function testUserFilterIsNotifiedWhenTheFilterIsBeingDetached(): void
    {
        $filter = $this->createMock(FilterInterface::class);
        $filter->method('getFilteredChainIdentifier')->willReturn(STREAM_FILTER_READ);
        $filter->expects(self::once())->method('filterDetachedEvent');

        $source_resource = fopen('php://memory', 'rb+');
        $handle          = StreamFilter::prependFilter($source_resource, $filter);
        StreamFilter::removeFilter($handle);
    }
}
