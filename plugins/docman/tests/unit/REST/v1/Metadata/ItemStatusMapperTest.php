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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

use Docman_Item;
use Docman_SettingsBo;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemStatusMapperTest extends TestCase
{
    private Docman_SettingsBo&MockObject $docman_setting_bo;

    public function setUp(): void
    {
        $this->docman_setting_bo = $this->createMock(Docman_SettingsBo::class);
    }

    public function testStatusIDCanBeFoundWhenAValidValueIsGiven(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('1');
        $status = $mapper->getItemStatusIdFromItemStatusString('rejected');

        self::assertEquals(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED, $status);
    }

    public function testTryingToFindIDForAnUnknownValueThrowsAnException(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('1');
        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('Status swang is invalid');
        $mapper->getItemStatusIdFromItemStatusString('swang');
    }

    public function testTryingToFindIDForNullThrowsAnException(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('1');

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('Status null is invalid');
        $mapper->getItemStatusIdFromItemStatusString(null);
    }

    public function testStatusIDExistsButTheMetadataIsNotEnabledThrowsExpection(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('0');

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('Status is not enabled for project');
        $mapper->getItemStatusIdFromItemStatusString('rejected');
    }

    public function testInheritanceStatusIDCanBeFoundWhenAValidValueIsGiven(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);
        $parent = new Docman_Item();

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('1');
        $status = $mapper->getItemStatusWithParentInheritance($parent, 'rejected');

        self::assertEquals(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED, $status);
    }

    public function testInheritanceTryingToFindIDForAnUnknownValueThrowsAnException(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);
        $parent = new Docman_Item();

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('1');
        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('Status swang is invalid');
        $mapper->getItemStatusWithParentInheritance($parent, 'swang');
    }

    public function testInheritanceTryingToFindIDForNullThrowsAnException(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);
        $parent = new Docman_Item(['status' => PLUGIN_DOCMAN_ITEM_STATUS_REJECTED]);

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('1');

        self::assertEquals(
            PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
            $mapper->getItemStatusWithParentInheritance($parent, null)
        );
    }

    public function testInheritanceStatusIDExistsButTheMetadataIsNotEnabledThrowsExpection(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);
        $parent = new Docman_Item();

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('0');

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('Status is not enabled for project');
        $mapper->getItemStatusWithParentInheritance($parent, 'rejected');
    }

    public function testInheritanceStatusIsInheritedFromParent(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);
        $parent = new Docman_Item();

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('0');

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('Status is not enabled for project');
        $mapper->getItemStatusWithParentInheritance($parent, 'rejected');
    }

    public function testItReturnsStringFromLegacyValue(): void
    {
        $mapper = new ItemStatusMapper($this->docman_setting_bo);

        $this->docman_setting_bo->method('getMetadataUsage')->willReturn('1');

        $status = $mapper->getItemStatusFromItemStatusNumber(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);

        self::assertEquals(ItemStatusMapper::ITEM_STATUS_APPROVED, $status);
    }
}
