<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusUsageMismatchException;
use Tuleap\Docman\REST\v1\Metadata\StatusNotFoundBadStatusGivenException;
use Tuleap\Docman\REST\v1\Metadata\StatusNotFoundNullException;

final class ItemStatusMapperTest extends TestCase
{
    use MockeryPHPUnitIntegration;


    /**
     * @var \Docman_SettingsBo|\Mockery\MockInterface
     */
    private $docman_setting_bo;

    public function setUp(): void
    {
        parent::setUp();
        $this->docman_setting_bo = \Mockery::mock(\Docman_SettingsBo::class);
    }

    public function testStatusIDCanBeFoundWhenAValidValueIsGiven(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->shouldReceive('getMetadataUsage')->andReturn('1');
        $status = $mapper->getItemStatusIdFromItemStatusString('rejected');

        $this->assertEquals(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED, $status);
    }

    public function testTryingToFindIDForAnUnknownValueThrowsAnException() : void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->shouldReceive('getMetadataUsage')->andReturn('1');
        $this->expectException(StatusNotFoundBadStatusGivenException::class);
        $mapper->getItemStatusIdFromItemStatusString('swang');
    }

    public function testTryingToFindIDForNullThrowsAnException(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->shouldReceive('getMetadataUsage')->andReturn('1');

        $this->expectException(StatusNotFoundNullException::class);
        $mapper->getItemStatusIdFromItemStatusString(null);
    }

    public function testStatusIDExistsButTheMetadataIsNotEnabledThrowsExpection(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->shouldReceive('getMetadataUsage')->andReturn('0');

        $this->expectException(ItemStatusUsageMismatchException::class);
        $mapper->getItemStatusIdFromItemStatusString('rejected');
    }
}
