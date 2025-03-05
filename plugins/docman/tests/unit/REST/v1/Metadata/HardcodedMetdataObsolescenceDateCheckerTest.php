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

use DateInterval;
use DateTimeImmutable;
use Docman_SettingsBo;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HardcodedMetdataObsolescenceDateCheckerTest extends TestCase
{
    private Docman_SettingsBo&MockObject $docman_settings_bo;

    public function setUp(): void
    {
        $this->docman_settings_bo = $this->createMock(Docman_SettingsBo::class);
    }

    public function testCheckObsolescenceDateUsageIsOkWhenMetadataUsageIsUsedAndADateIsSet(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->method('getMetadataUsage')
            ->with('obsolescence_date')
            ->willReturn('1');

        $checker->checkObsolescenceDateUsageForDocument('2019-06-04');

        self::expectNotToPerformAssertions();
    }

    public function testCheckObsolescenceDateUsageIsOkWhenMetadataUsageIsNotUsedAndTheDateIs0(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->method('getMetadataUsage')
            ->with('obsolescence_date')
            ->willReturn('0');

        $checker->checkObsolescenceDateUsageForDocument(ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        self::expectNotToPerformAssertions();
    }

    public function testCheckObsolescenceDateUsageIsOk(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->method('getMetadataUsage')
            ->with('obsolescence_date')
            ->willReturn('1');

        $checker->checkObsolescenceDateUsageForDocument('2019-06-04');

        self::expectNotToPerformAssertions();
    }

    public function testCheckObsolescenceDateUsageIsOkIfTheMetadataIsUsedAndObsolescenceDateIs0(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        self::expectNotToPerformAssertions();

        $checker->checkObsolescenceDateUsageForDocument(ItemRepresentation::OBSOLESCENCE_DATE_NONE);
    }

    public function testCheckObsolescenceDateUsageThrowsExceptionIfTheMetadataIsNotUsedAndObsolescenceDateIsNot0(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->method('getMetadataUsage')
            ->with('obsolescence_date')
            ->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('obsolescence date is not enabled for project');

        $checker->checkObsolescenceDateUsageForDocument('2019-06-04');
    }

    public function testCheckDateValidityIsOk(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $current_date      = new DateTimeImmutable();
        $obsolescence_date = $current_date->add(new DateInterval('P1D'));

        $checker->checkDateValidity(
            $current_date->getTimestamp(),
            $obsolescence_date->getTimestamp(),
        );
        self::expectNotToPerformAssertions();
    }

    public function testCheckDateValidityThrowsExceptionIfTheObsolescenceDateIsGreaterThanTheCurrentDate(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $obsolescence_date = new DateTimeImmutable();
        $current_date      = $obsolescence_date->add(new DateInterval('P1D'));

        $this->docman_settings_bo->method('getMetadataUsage')
            ->with('obsolescence_date')
            ->willReturn('1');

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('obsolescence date before today');
        $checker->checkDateValidity(
            $current_date->getTimestamp(),
            $obsolescence_date->getTimestamp(),
        );
    }

    public function testCheckDateValidityIsOkWhenTheObsolescenceDateIs0(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $obsolescence_date = new DateTimeImmutable();

        $this->docman_settings_bo->expects(self::never())->method('getMetadataUsage');

        $checker->checkDateValidity(
            (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE,
            $obsolescence_date->getTimestamp(),
        );
    }
}
