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

final class SHA256ComputeOnReadFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testSHA256IsComputed(): void
    {
        $test_string = 'The quick brown fox jumps over the lazy dog';

        $source_resource = fopen('php://memory', 'rb+');

        $sha256_filter = new SHA256ComputeOnReadFilter();
        StreamFilter::prependFilter($source_resource, $sha256_filter);

        fwrite($source_resource, $test_string);
        rewind($source_resource);
        $destination_resource = fopen('php://memory', 'rb+');
        stream_copy_to_stream($source_resource, $destination_resource);
        rewind($destination_resource);
        self::assertSame($test_string, stream_get_contents($destination_resource));
        self::assertSame('d7a8fbb307d7809469ca9abcb0082e4f8d5651e46d3cdb762d02d0bf37c9e592', $sha256_filter->getHashValue());
        fclose($destination_resource);

        fwrite($source_resource, '.');
        fseek($source_resource, -1, SEEK_CUR);
        fread($source_resource, 1);
        self::assertSame('ef537f25c895bfa782526529a9b63d97aa631564d5d789c2b765448c8635fb6c', $sha256_filter->getHashValue());
        fclose($source_resource);
        self::assertSame('ef537f25c895bfa782526529a9b63d97aa631564d5d789c2b765448c8635fb6c', $sha256_filter->getHashValue());
    }
}
