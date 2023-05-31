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

final class FilterHandleTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testFilterHandleCanBeCreatedFromAStreamFilter(): void
    {
        $stream        = fopen('php://memory', 'rb');
        $stream_filter = stream_filter_prepend($stream, 'string.rot13');

        $filter_handle = new FilterHandle($stream_filter);

        self::assertSame($stream_filter, $filter_handle->getFilterResource());
        fclose($stream);
    }

    public function testFilterHandleCreationRejectsInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $stream_filter = false;
        new FilterHandle($stream_filter);
    }

    public function testFilterHandleCreationIsRejectedWhenGivenResourceIsNotAStream(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $stream = fopen('php://memory', 'rb');
        try {
            new FilterHandle($stream);
        } finally {
            fclose($stream);
        }
    }
}
