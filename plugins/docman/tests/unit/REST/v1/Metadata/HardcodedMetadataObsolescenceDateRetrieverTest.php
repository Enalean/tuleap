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
use Tuleap\Docman\REST\v1\ItemRepresentation;

class HardcodedMetadataObsolescenceDateRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
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

    public function testGetTimeStampOfDateForDocumentAtUpdate(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $current_time                = \DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-20');
        $obsolescence_date           = \DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-21');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $this->metadata_obsolescence_date_checker->shouldReceive('checkObsolescenceDateUsageForDocument')
            ->withArgs(
                [
                    $obsolescence_date_formatted,
                ]
            )->once();
        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');
        $this->metadata_obsolescence_date_checker->shouldReceive('checkDateValidity')->never();

        $time_stamp              = $retriever->getTimeStampOfDate(
            $obsolescence_date_formatted,
            $current_time
        );
        $expected_date_timestamp = $obsolescence_date->getTimestamp();
        $this->assertEquals($expected_date_timestamp, $time_stamp);
    }

    public function testGetTimeStampOfDateForDocumentAtCreation(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $current_time                = \DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-20');
        $obsolescence_date           = \DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-21');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $this->metadata_obsolescence_date_checker->shouldReceive('checkObsolescenceDateUsageForDocument')
            ->withArgs(
                [
                    $obsolescence_date_formatted,
                ]
            )->once();
        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');
        $this->metadata_obsolescence_date_checker->shouldReceive('checkDateValidity')->once();

        $time_stamp              = $retriever->getTimeStampOfDateWithoutPeriodValidity(
            $obsolescence_date_formatted,
            $current_time
        );
        $expected_date_timestamp = $obsolescence_date->getTimestamp();
        $this->assertEquals($expected_date_timestamp, $time_stamp);
    }

    public function testGetTimeStampOfDateForDocumentWhichHaveAnUnlimitedObsolescenceDate(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->shouldReceive('checkObsolescenceDateUsageForDocument')
            ->withArgs([ItemRepresentation::OBSOLESCENCE_DATE_NONE])
            ->once();

        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');

        $time_stamp = $retriever->getTimeStampOfDate(null, new \DateTimeImmutable());

        $this->assertEquals(0, $time_stamp);
    }

    public function testGetTimeStampOfDateThrowsExceptionWhenTheDateIsNotWellFormattedAndTheMetadataIsUsed(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $current_time                 = \DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-20');
        $obsolescence_date_bad_format = ' 2018-02-56459595';

        $this->metadata_obsolescence_date_checker->shouldReceive('checkObsolescenceDateUsageForDocument')->withArgs(
            [$obsolescence_date_bad_format]
        )->once();
        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');
        $this->metadata_obsolescence_date_checker->shouldReceive('checkDateValidity')->never();

        $this->expectException(HardCodedMetadataException::class);
        $this->expectExceptionMessage('obsolescence date format is incorrect');

        $retriever->getTimeStampOfDate($obsolescence_date_bad_format, $current_time);
    }

    public function testGetTimeStampOfDateReturns0IfTheDateIsNull(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->shouldReceive('checkObsolescenceDateUsageForDocument')
            ->withArgs([null])
            ->once();

        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn('1');

        $time_stamp = $retriever->getTimeStampOfDate(null, new \DateTimeImmutable());

        $this->assertEquals(0, $time_stamp);
    }

    public function testGetTimeStampOfDateWithoutPeriodValidityReturn0IfObsolescenceDateIsNotUsed(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->shouldReceive('isObsolescenceMetadataUsed')->andReturn(false);
        $this->metadata_obsolescence_date_checker->shouldReceive('checkDateValidity')->never();

        $time_stamp = $retriever->getTimeStampOfDateWithoutPeriodValidity('2020-09-20', new \DateTimeImmutable());

        $this->assertEquals(0, $time_stamp);
    }
}
