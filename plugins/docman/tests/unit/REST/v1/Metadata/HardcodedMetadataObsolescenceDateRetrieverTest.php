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

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HardcodedMetadataObsolescenceDateRetrieverTest extends TestCase
{
    private HardcodedMetdataObsolescenceDateChecker&MockObject $metadata_obsolescence_date_checker;

    public function setUp(): void
    {
        $this->metadata_obsolescence_date_checker = $this->createMock(HardcodedMetdataObsolescenceDateChecker::class);
    }

    public function testGetTimeStampOfDateForDocumentAtUpdate(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $obsolescence_date           = DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-21');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $this->metadata_obsolescence_date_checker->expects($this->once())->method('checkObsolescenceDateUsageForDocument')
            ->with($obsolescence_date_formatted);
        $this->metadata_obsolescence_date_checker->method('isObsolescenceMetadataUsed')->willReturn(true);
        $this->metadata_obsolescence_date_checker->expects(self::never())->method('checkDateValidity');

        $time_stamp              = $retriever->getTimeStampOfDate($obsolescence_date_formatted);
        $expected_date_timestamp = $obsolescence_date->getTimestamp();
        self::assertEquals($expected_date_timestamp, $time_stamp);
    }

    public function testGetTimeStampOfDateForDocumentAtCreation(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $current_time                = DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-20');
        $obsolescence_date           = DateTimeImmutable::createFromFormat('Y-m-d', '2019-02-21');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $this->metadata_obsolescence_date_checker->expects($this->once())->method('checkObsolescenceDateUsageForDocument')
            ->with($obsolescence_date_formatted);
        $this->metadata_obsolescence_date_checker->method('isObsolescenceMetadataUsed')->willReturn(true);
        $this->metadata_obsolescence_date_checker->expects($this->once())->method('checkDateValidity');

        $time_stamp              = $retriever->getTimeStampOfDateWithoutPeriodValidity(
            $obsolescence_date_formatted,
            $current_time
        );
        $expected_date_timestamp = $obsolescence_date->getTimestamp();
        self::assertSame(
            (new DateTimeImmutable())->setTimestamp($expected_date_timestamp)->format('Y-m-d'),
            (new DateTimeImmutable())->setTimestamp($time_stamp)->format('Y-m-d'),
        );
    }

    public function testGetTimeStampOfDateForDocumentWhichHaveAnUnlimitedObsolescenceDate(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->expects($this->once())->method('checkObsolescenceDateUsageForDocument')
            ->with(ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $this->metadata_obsolescence_date_checker->method('isObsolescenceMetadataUsed')->willReturn(true);

        $time_stamp = $retriever->getTimeStampOfDate(null);

        self::assertEquals(0, $time_stamp);
    }

    public function testGetTimeStampOfDateThrowsExceptionWhenTheDateIsNotWellFormattedAndTheMetadataIsUsed(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $obsolescence_date_bad_format = ' 2018-02-56459595';

        $this->metadata_obsolescence_date_checker->expects($this->once())->method('checkObsolescenceDateUsageForDocument')->with($obsolescence_date_bad_format);
        $this->metadata_obsolescence_date_checker->method('isObsolescenceMetadataUsed')->willReturn(true);
        $this->metadata_obsolescence_date_checker->expects(self::never())->method('checkDateValidity');

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('obsolescence date format is incorrect');

        $retriever->getTimeStampOfDate($obsolescence_date_bad_format);
    }

    public function testGetTimeStampOfDateReturns0IfTheDateIsNull(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->expects($this->once())->method('checkObsolescenceDateUsageForDocument')->with(null);

        $this->metadata_obsolescence_date_checker->method('isObsolescenceMetadataUsed')->willReturn(true);

        $time_stamp = $retriever->getTimeStampOfDate(null);

        self::assertEquals(0, $time_stamp);
    }

    public function testGetTimeStampOfDateWithoutPeriodValidityReturn0IfObsolescenceDateIsNotUsed(): void
    {
        $retriever = new HardcodedMetadataObsolescenceDateRetriever($this->metadata_obsolescence_date_checker);

        $this->metadata_obsolescence_date_checker->method('isObsolescenceMetadataUsed')->willReturn(false);
        $this->metadata_obsolescence_date_checker->expects(self::never())->method('checkDateValidity');

        $time_stamp = $retriever->getTimeStampOfDateWithoutPeriodValidity('2020-09-20', new DateTimeImmutable());

        self::assertEquals(0, $time_stamp);
    }
}
