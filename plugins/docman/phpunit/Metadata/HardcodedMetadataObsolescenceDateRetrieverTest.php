<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HardcodedMetadataObsolescenceDateRetrieverTest extends TestCase
{

    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface|HardcodedMetdataObsolescenceDateChecker
     */
    private $metadata_obsolescence_date_checker;

    public function setUp(): void
    {
        parent::setUp();

        $this->metadata_obsolescence_date_checker = \Mockery::mock(HardcodedMetdataObsolescenceDateChecker::class);
    }

    public function testGetTimeStampOfDateForFolder(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');

        $time_stamp = $retriever->getTimeStampOfDate('2019-02-25', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);

        $this->assertEquals(0, $time_stamp);
    }

    public function testGetTimeStampOfDateForDocument(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');

        $time_stamp = $retriever->getTimeStampOfDate('2019-02-25', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);

        $expected_date = \DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-25');
        $this->assertEquals($expected_date->getTimestamp(), $time_stamp);
    }

    public function testGetTimeStampOfDateWhenTheObsolescenceDateIsNotUsed(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('0');

        $time_stamp = $retriever->getTimeStampOfDate(null, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);
        $this->assertEquals(0, $time_stamp);
    }

    public function testGetTimeStampOfDateThrowsExceptionWhenTheDateIsNotWellFormattedAndTheMetadataIsUsed(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');

        $this->expectException(InvalidDateTimeFormatException::class);

        $retriever->getTimeStampOfDate('2018-02-56459595', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);
    }
}
