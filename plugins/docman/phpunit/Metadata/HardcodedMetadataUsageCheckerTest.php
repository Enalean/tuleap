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

namespace Tuleap\Docman\Metadata;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataUsageChecker;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusUsageMismatchException;
use Tuleap\Docman\REST\v1\Metadata\StatusNotFoundException;

class HardcodedMetadataUsageCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Docman_SettingsBo|\Mockery\MockInterface
     */
    private $docman_settings_bo;

    public function setUp(): void
    {
        parent::setUp();

        $this->docman_settings_bo = \Mockery::mock(\Docman_SettingsBo::class);
    }

    public function testCheckItemStatusUsageIsOkIfTheUsageIsAllowed(): void
    {
        $checker = new HardcodedMetadataUsageChecker(
            $this->docman_settings_bo
        );

        $this->docman_settings_bo->shouldReceive("getMetadataUsage")->with('status')->andReturn('1');

        $checker->checkStatusIsNotSetWhenStatusMetadataIsNotAllowed(ItemStatusMapper::ITEM_STATUS_APPROVED);
    }

    public function testCheckItemStatusUsageThrowsExceptionIfTheUsageIsNotAllowed(): void
    {
        $checker = new HardcodedMetadataUsageChecker(
            $this->docman_settings_bo
        );

        $this->docman_settings_bo->shouldReceive("getMetadataUsage")->with('status')->andReturn('0');
        $this->expectException(ItemStatusUsageMismatchException::class);

        $checker->checkStatusIsNotSetWhenStatusMetadataIsNotAllowed(ItemStatusMapper::ITEM_STATUS_APPROVED);
    }

    public function testCheckItemStatusAuthorisedValueIsOk(): void
    {
        $checker = new HardcodedMetadataUsageChecker(
            $this->docman_settings_bo
        );

        $this->docman_settings_bo->shouldReceive("getMetadataUsage")->with('status')->andReturn('1');

        $checker->checkItemStatusAuthorisedValue('rejected');

        $this->addToAssertionCount(1);
    }

    public function testCheckItemStatusAuthorisedValueThrowsExceptionIfTheValueIsNotAuthorized(): void
    {
        $checker = new HardcodedMetadataUsageChecker(
            $this->docman_settings_bo
        );

        $this->docman_settings_bo->shouldReceive("getMetadataUsage")->with('status')->andReturn('1');

        $this->expectException(StatusNotFoundException::class);

        $checker->checkItemStatusAuthorisedValue('swang');
    }

    public function testCheckItemStatusAuthorisedValueThrowsExceptionIfTheValueIsNull(): void
    {
        $checker = new HardcodedMetadataUsageChecker(
            $this->docman_settings_bo
        );

        $this->docman_settings_bo->shouldReceive("getMetadataUsage")->with('status')->andReturn('1');

        $this->expectException(StatusNotFoundException::class);

        $checker->checkItemStatusAuthorisedValue(null);
    }


    public function testCheckItemStatusUsageReturnExceptionIfTheMetadataReturnFalse(): void
    {
        $checker = new HardcodedMetadataUsageChecker(
            $this->docman_settings_bo
        );
        $this->docman_settings_bo->shouldReceive("getMetadataUsage")->with('status')->andReturn(false);

        $this->expectException(ItemStatusUsageMismatchException::class);

        $checker->checkStatusIsNotSetWhenStatusMetadataIsNotAllowed('approved');
    }
}
