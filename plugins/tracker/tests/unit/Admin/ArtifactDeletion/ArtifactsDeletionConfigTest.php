<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin\ArtifactDeletion;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactsDeletionConfigTest extends TestCase
{
    private ArtifactsDeletionConfigDAO|\PHPUnit\Framework\MockObject\MockObject $dao;
    private ArtifactsDeletionConfig $config;

    protected function setUp(): void
    {
        $this->dao    = $this->createMock(ArtifactsDeletionConfigDAO::class);
        $this->config = new ArtifactsDeletionConfig($this->dao);
    }

    #[\PHPUnit\Framework\Attributes\TestWith([0])]
    #[\PHPUnit\Framework\Attributes\TestWith([10])]
    public function testItRetrievesDeletionLimitAndCachesIt(int $limit): void
    {
        $this->dao->expects($this->once())->method('searchDeletableArtifactsLimit')
            ->willReturn([['value' => $limit]]);
        self::assertSame($limit, $this->config->getArtifactsDeletionLimit());
        // method is called twice to test that db is only called once and cache is effective
        self::assertSame($limit, $this->config->getArtifactsDeletionLimit());
    }
}
