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

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact;

use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactNotFoundException;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactFactoryAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 644;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);
    }

    private function getAdapter(): ArtifactFactoryAdapter
    {
        return new ArtifactFactoryAdapter($this->artifact_factory);
    }

    public function testItReturnsArtifactFromIdentifier(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->build();
        $this->artifact_factory->method('getArtifactById')->willReturn($artifact);

        $result = $this->getAdapter()->getNonNullArtifact(ArtifactIdentifierStub::withId(self::ARTIFACT_ID));

        self::assertSame($artifact, $result);
    }

    public function testItThrowsWhenIdentifierDoesNotMatchAnyArtifact(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);

        $this->expectException(ArtifactNotFoundException::class);
        $this->getAdapter()->getNonNullArtifact(ArtifactIdentifierStub::withId(404));
    }
}
