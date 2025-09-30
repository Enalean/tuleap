<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Search;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AlwaysThereColumnRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \Docman_SettingsBo&\PHPUnit\Framework\MockObject\MockObject
     */
    private $docman_settings;
    private AlwaysThereColumnRetriever $retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->docman_settings = $this->createMock(\Docman_SettingsBo::class);
        $this->retriever       = new AlwaysThereColumnRetriever($this->docman_settings);
    }

    public function testItReturnsColumnsWithStatus(): void
    {
        $this->docman_settings->method('getMetadataUsage')->with('status')->willReturn(true);
        $columns = $this->retriever->getColumns();

        self::assertContains('status', $columns);
        self::assertNotEmpty($columns);
    }

    public function testItReturnsColumnWithoutStatus(): void
    {
        $this->docman_settings->method('getMetadataUsage')->with('status')->willReturn(false);
        $columns = $this->retriever->getColumns();

        self::assertNotContains('status', $columns);
        self::assertNotEmpty($columns);
    }
}
