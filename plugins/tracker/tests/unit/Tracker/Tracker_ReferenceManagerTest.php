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

use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_ReferenceManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Tracker_ReferenceManager
     */
    private $tracker_reference_manager;
    /**
     * @var string
     */
    private $keyword;
    /**
     * @var int
     */
    private $artifact_id;
    /**
     * @var Artifact
     */
    private $artifact;

    protected function setUp(): void
    {
        $this->reference_manager = \Mockery::spy(\ReferenceManager::class);
        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $this->tracker_reference_manager = new Tracker_ReferenceManager(
            $this->reference_manager,
            $this->artifact_factory
        );

        $this->keyword     = 'art';
        $this->artifact_id = 101;
        $tracker           = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getName')->andReturn('My tracker');
        $this->artifact = new Artifact($this->artifact_id, 101, null, 10, null);
        $this->artifact->setTracker($tracker);
    }


    public function testItReturnsNullIfThereIsNoArtifactMatching(): void
    {
        $this->artifact_factory->shouldReceive('getArtifactById')->with(101)->andReturns(null);

        $reference = $this->tracker_reference_manager->getReference(
            $this->keyword,
            $this->artifact_id
        );

        $this->assertNull($reference);
    }

    public function testItReturnsTheTV5LinkIfIdIsMatching(): void
    {
        $this->artifact_factory->shouldReceive('getArtifactById')->with(101)->andReturns($this->artifact);

        $reference = $this->tracker_reference_manager->getReference(
            $this->keyword,
            $this->artifact_id
        );

        $this->assertNotNull($reference);
        $this->assertInstanceOf(\Tracker_Reference::class, $reference);
    }
}
