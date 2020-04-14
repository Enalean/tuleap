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

namespace Tuleap\GitLFS\Transfer;

use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\StreamFilter\StreamFilter;
use Tuleap\Instrument\Prometheus\Prometheus;

class BytesAmountHandledLFSObjectInstrumentationFilterTest extends TestCase
{
    public function testReceivedBytesAreCounted(): void
    {
        $input_source = fopen('php://memory', 'rb+');

        $prometheus = Prometheus::getInMemory();
        $filter     = BytesAmountHandledLFSObjectInstrumentationFilter::buildReceivedBytesFilter($prometheus);
        StreamFilter::prependFilter($input_source, $filter);

        $size       = 1024;
        fwrite($input_source, \str_repeat('A', $size));
        rewind($input_source);
        $destination_resource = fopen('php://memory', 'wb');
        stream_copy_to_stream($input_source, $destination_resource);

        fclose($input_source);
        fclose($destination_resource);

        $this->assertMatchesRegularExpression("/(.*)gitlfs_object_receive_bytes(.*)$size/", $prometheus->renderText());
    }

    public function testTransmittedBytesAreCounted(): void
    {
        $input_source = fopen('php://memory', 'rb+');

        $prometheus    = Prometheus::getInMemory();
        $transfer_type = 'test';
        $filter        = BytesAmountHandledLFSObjectInstrumentationFilter::buildTransmittedBytesFilter($prometheus, $transfer_type);
        StreamFilter::prependFilter($input_source, $filter);

        $size       = 1024;
        fwrite($input_source, \str_repeat('A', $size));
        rewind($input_source);
        $destination_resource = fopen('php://memory', 'wb');
        stream_copy_to_stream($input_source, $destination_resource);

        fclose($input_source);
        fclose($destination_resource);

        $this->assertMatchesRegularExpression("/(.*)gitlfs_object_transmit_bytes(.*)$transfer_type(.*)$size/", $prometheus->renderText());
    }
}
