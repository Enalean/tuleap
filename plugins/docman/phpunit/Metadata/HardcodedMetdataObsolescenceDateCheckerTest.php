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

namespace Tuleap\Docman\REST\v1\Metadata;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HardcodedMetdataObsolescenceDateCheckerTest extends TestCase
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

    public function testCheckObsolescenceDateUsageIsOkWhenMetadataUsageIsUsedAndADateIsSet(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->with('obsolescence_date')
                                 ->andReturn("1");

        $checker->checkObsolescenceDateUsage('2019-06-04', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);

        $this->addToAssertionCount(1);
    }

    public function testCheckObsolescenceDateUsageIsOkWhenMetadataUsageIsNotUsedAndTheDateIs0(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->with('obsolescence_date')
                                 ->andReturn("0");

        $checker->checkObsolescenceDateUsage('0', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);

        $this->addToAssertionCount(1);
    }

    public function testCheckObsolescenceDateUsageIsOk(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->with('obsolescence_date')
                                 ->andReturn("1");

        $checker->checkObsolescenceDateUsage('2019-06-04', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);

        $this->addToAssertionCount(1);
    }

    public function testCheckObsolescenceDateUsageIsOkWithFolder(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->never();

        $checker->checkObsolescenceDateUsage('2019-06-04', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);

        $this->addToAssertionCount(1);
    }

    public function testCheckObsolescenceDateUsageIsOkIfTheMetadataIsUsedAndObsolescenceDateIs0(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->with('obsolescence_date')
                                 ->andReturn('1');

        $checker->checkObsolescenceDateUsage('0', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);
    }

    public function testCheckObsolescenceDateUsageThrowsExceptionIfTheMetadataIsNotUsedAndObsolescenceDateIsNot0(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->with('obsolescence_date')
                                 ->andReturn('0');

        $this->expectException(ObsolescenceDateDisabledException::class);

        $checker->checkObsolescenceDateUsage('2019-06-04', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);
    }

    public function testCheckDateValidityIsOk(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $current_date      = new DateTimeImmutable();
        $obsolescence_date = $current_date->add(new \DateInterval('P1D'));

        $checker->checkDateValidity(
            $current_date->getTimestamp(),
            $obsolescence_date->getTimestamp(),
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );
        $this->addToAssertionCount(1);
    }

    public function testCheckDateValidityThrowsExceptionIfTheObsolescenceDateIsGreaterThanTheCurrentDate(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $obsolescence_date = new DateTimeImmutable();
        $current_date      = $obsolescence_date->add(new \DateInterval('P1D'));

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->with('obsolescence_date')
                                 ->andReturn('1');

        $this->expectException(InvalidDateComparisonException::class);
        $checker->checkDateValidity(
            $current_date->getTimestamp(),
            $obsolescence_date->getTimestamp(),
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY
        );
    }

    public function testCheckDateValidityIfTheItemIsAFolder(): void
    {
        $checker = new HardcodedMetdataObsolescenceDateChecker($this->docman_settings_bo);

        $obsolescence_date = new DateTimeImmutable();
        $current_date      = $obsolescence_date->add(new \DateInterval('P1D'));

        $this->docman_settings_bo->shouldReceive('getMetadataUsage')
                                 ->never();

        $checker->checkDateValidity(
            $current_date->getTimestamp(),
            $obsolescence_date->getTimestamp(),
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER
        );
    }
}
