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

namespace Tuleap\Cardwall\Column;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class ColumnColorRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetHeaderColorNameOrRGBReturnsRGBColor(): void
    {
        $row   = ['bg_red' => 255, 'bg_green' => 128, 'bg_blue' => 0, 'tlp_color_name' => null];
        $color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);

        $this->assertSame("rgb(255, 128, 0)", $color);
    }

    public function testGetHeaderColorNameOrRGBReturnsTLPColorName(): void
    {
        $row   = ['bg_red' => null, 'bg_green' => null, 'bg_blue' => null, 'tlp_color_name' => 'fiesta-red'];
        $color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);

        $this->assertSame('fiesta-red', $color);
    }

    public function testGetHeaderColorNameOrRGBReturnsTLPColorEvenWhenRGBIsNotNull(): void
    {
        $row   = ['bg_red' => 255, 'bg_green' => 0, 'bg_blue' => 0, 'tlp_color_name' => 'fiesta-red'];
        $color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);

        $this->assertSame('fiesta-red', $color);
    }

    public function testGetHeaderColorNameOrRGBReturnsDefaultColorWhenOneColorIsNull(): void
    {
        $row   = ['bg_red' => 255, 'bg_green' => null, 'bg_blue' => 0, 'tlp_color_name' => null];
        $color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);

        $this->assertSame(\Cardwall_OnTop_Config_ColumnFactory::DEFAULT_HEADER_COLOR, $color);
    }

    public function testGetHeaderColorNameOrHexReturnsHexColor(): void
    {
        $row   = ['bg_red' => 255, 'bg_green' => 128, 'bg_blue' => 0, 'tlp_color_name' => null];
        $color = ColumnColorRetriever::getHeaderColorNameOrHex($row);

        $this->assertSame('#FF8000', $color);
    }

    public function testGetHeaderColorNameOrHexReturnsTLPColorName(): void
    {
        $row   = ['bg_red' => null, 'bg_green' => null, 'bg_blue' => null, 'tlp_color_name' => 'fiesta-red'];
        $color = ColumnColorRetriever::getHeaderColorNameOrHex($row);

        $this->assertSame('fiesta-red', $color);
    }

    public function testGetHeaderColorNameOrHexReturnsTLPColorEvenWhenHexIsNotNull(): void
    {
        $row   = ['bg_red' => 255, 'bg_green' => 0, 'bg_blue' => 0, 'tlp_color_name' => 'fiesta-red'];
        $color = ColumnColorRetriever::getHeaderColorNameOrHex($row);

        $this->assertSame('fiesta-red', $color);
    }

    public function testGetHeaderColorNameOrHexReturnsEmptyStringWhenOneColorIsNull(): void
    {
        $row   = ['bg_red' => 255, 'bg_green' => null, 'bg_blue' => 0, 'tlp_color_name' => null];
        $color = ColumnColorRetriever::getHeaderColorNameOrHex($row);

        $this->assertSame('', $color);
    }
}
