<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Events\MoveArtifactCheckExternalSemantics;

require_once __DIR__ . '/../../bootstrap.php';

class MoveSemanticCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MoveSemanticChecker
     */
    private $checker;

    protected function setUp()
    {
        parent::setUp();

        $this->event_manager = Mockery::spy(\EventManager::class);
        $this->checker       = new MoveSemanticChecker($this->event_manager);

        $this->source_tracker = Mockery::mock(\Tracker::class);
        $this->target_tracker = Mockery::mock(\Tracker::class);
    }

    public function testSemanticAreAlignedIfBothTrackersHaveBothSemantics()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyTitleSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->assertTrue($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyDescriptionSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfSourceTrackersHasBothSemanticsAndTargetHasOnlyTitleSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->assertTrue($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfSourceTrackersHasBothSemanticsAndTargetHasOnlyDescriptionSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreNotAlignedIfSourceTrackerHasDescriptionAndTargetHasTitle()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreNotAlignedIfSourceTrackerHasTitleAndTargetHasDescription()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreNotAlignedIfSourceTrackerHasNoSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testExternalSemanticsAreNotCheckedIfNotReturnedByOtherPlugin()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactCheckExternalSemantics $event) {
            return true;
        }));

        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticsAreNotAlignedIfBothTrackerAndExternalSemanticsAreNotAligne()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactCheckExternalSemantics $event) {
            $event->setVisitedByPlugin();
            return true;
        }));

        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertFalse($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticsAreAlignedIfExternalSemanticsAreAlignedAndNotTrackerSemantics()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactCheckExternalSemantics $event) {
            $event->setVisitedByPlugin();
            $event->setExternalSemanticAligned();
            return true;
        }));

        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->checker->areSemanticsAligned($this->source_tracker, $this->target_tracker));
    }
}
