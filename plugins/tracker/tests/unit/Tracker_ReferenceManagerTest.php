<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_ReferenceManagerTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Tuleap\GlobalLanguageMock;

    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Tracker_ReferenceManager $tracker_reference_manager;
    private string $keyword  = 'art';
    private int $artifact_id = 101;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);

        $this->tracker_reference_manager = new Tracker_ReferenceManager(
            ReferenceManager::instance(),
            $this->artifact_factory
        );

        $tracker        = TrackerTestBuilder::aTracker()->build();
        $this->artifact = new Artifact($this->artifact_id, $tracker->getId(), 0, 10, null);
        $this->artifact->setTracker($tracker);
    }

    public function testItReturnsNullIfThereIsNoArtifactMatching(): void
    {
        $this->artifact_factory->method('getArtifactById')->with(101)->willReturn(null);

        $reference = $this->tracker_reference_manager->getReference(
            $this->keyword,
            $this->artifact_id
        );

        $this->assertNull($reference);
    }

    public function testItReturnsTheTV5LinkIfIdIsMatching(): void
    {
        $this->artifact_factory->method('getArtifactById')->with(101)->willReturn($this->artifact);

        $reference = $this->tracker_reference_manager->getReference(
            $this->keyword,
            $this->artifact_id
        );

        $this->assertNotNull($reference);
        $this->assertInstanceOf(\Tracker_Reference::class, $reference);
    }
}
