<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Git\DiskUsage;

use Statistics_DiskUsageDao;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private readonly Statistics_DiskUsageDao $dao;
    private readonly Retriever $retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao       = $this->createMock(Statistics_DiskUsageDao::class);
        $this->retriever = new Retriever($this->dao);
    }

    public function testReturnsValueGivenByTheDB(): void
    {
        $this->dao->method('getLastSizeForService')->willReturn(['size' => '10']);

        self::assertEquals(10, $this->retriever->getLastSizeForProject(ProjectTestBuilder::aProject()->build()));
    }

    public function testReturns0WhenNoValueExistsInDB(): void
    {
        $this->dao->method('getLastSizeForService')->willReturn(false);

        self::assertEquals(0, $this->retriever->getLastSizeForProject(ProjectTestBuilder::aProject()->build()));
    }
}
