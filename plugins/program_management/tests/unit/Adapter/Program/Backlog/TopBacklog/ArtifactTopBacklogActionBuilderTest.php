<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\Layout\JavascriptAsset;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerSemantics;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\VerifyFeaturePlanned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\VerifyIsInTopBacklog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\TopBacklogActionArtifactSourceInformation;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeaturePlannedStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsInTopBacklogStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsPlannableStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Test\Builders\IncludeAssetsBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ArtifactTopBacklogActionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BuildProgram $build_program;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\Stub
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\TrackerFactory
     */
    private $tracker_factory;
    private VerifyIsPlannable $verify_is_plannable;
    private VerifyIsInTopBacklog $verify_is_in_top_backlog_stub;
    private VerifyFeaturePlanned $verify_feature_planned;


    protected function setUp(): void
    {
        $this->build_program                 = BuildProgramStub::stubValidProgram();
        $this->verify_is_plannable           = VerifyIsPlannableStub::buildPlannableElement();
        $this->verify_is_in_top_backlog_stub = VerifyIsInTopBacklogStub::buildIsInTopBacklog();
        $this->verify_feature_planned        = VerifyFeaturePlannedStub::isPlanned();
        $this->tracker_factory               = $this->createStub(\TrackerFactory::class);
        $this->user                          = $this->createStub(\PFUser::class);
        $this->user->method('isSuperUser')->willReturn(true);
        $this->user->method('isAdmin')->willReturn(true);
        $this->user->method('getId')->willReturn(101);
        $this->user->method('getUserName')->willReturn("John");
    }

    public function testBuildsActionForAnUnplannedArtifact(): void
    {
        $source_information                  = new TopBacklogActionArtifactSourceInformation(888, 140, 102);
        $this->build_program                 = BuildProgramStub::stubValidProgram();
        $this->verify_is_in_top_backlog_stub = VerifyIsInTopBacklogStub::buildNotInBacklog();
        $this->verify_feature_planned        = VerifyFeaturePlannedStub::isNotPlanned();
        $this->mockAValidTracker();

        self::assertNotNull($this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user));
    }

    public function testBuildsActionForAnArtifactInTheTopBacklog(): void
    {
        $source_information  = new TopBacklogActionArtifactSourceInformation(999, 140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->mockAValidTracker();

        self::assertNotNull($this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user));
    }

    public function testNoActionIsBuiltForArtifactsThatAreNotInAProgramProject(): void
    {
        $source_information  = new TopBacklogActionArtifactSourceInformation(400, 140, 102);
        $this->build_program = BuildProgramStub::stubInvalidProgram();
        $this->mockAValidTracker();
        self::assertNull($this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user));
    }

    public function testNoActionIsBuiltForUsersThatCannotPrioritizeFeatures(): void
    {
        $source_information  = new TopBacklogActionArtifactSourceInformation(401, 140, 102);
        $this->build_program = BuildProgramStub::stubValidProgram();
        $this->mockAValidTracker();

        self::assertNull($this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user));
    }

    public function testNoActionIsBuiltForArtifactsThatAreNotPlannable(): void
    {
        $source_information                  = new TopBacklogActionArtifactSourceInformation(2, 140, 102);
        $this->build_program                 = BuildProgramStub::stubValidProgram();
        $this->verify_is_in_top_backlog_stub = VerifyIsInTopBacklogStub::buildNotInBacklog();
        $this->verify_is_plannable           = VerifyIsPlannableStub::buildNotPlannableElement();
        $this->mockAValidTracker();

        self::assertNull($this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user));
    }

    public function testNoActionIsBuiltForArtifactsThatArePlannedInAProgramIncrement(): void
    {
        $source_information                  = new TopBacklogActionArtifactSourceInformation(3, 140, 102);
        $this->build_program                 = BuildProgramStub::stubValidProgram();
        $this->verify_is_in_top_backlog_stub = VerifyIsInTopBacklogStub::buildNotInBacklog();
        $this->mockAValidTracker();

        self::assertNull($this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user));
    }

    public function testDisabledActionIsBuiltWhenTitleIsNotDefined(): void
    {
        $source_information                  = new TopBacklogActionArtifactSourceInformation(888, 140, 102);
        $this->build_program                 = BuildProgramStub::stubValidProgram();
        $this->verify_is_in_top_backlog_stub = VerifyIsInTopBacklogStub::buildNotInBacklog();
        $this->verify_feature_planned        = VerifyFeaturePlannedStub::isNotPlanned();
        $tracker                             = $this->createStub(\Tracker::class);
        $tracker->method('hasSemanticsTitle')->willReturn(false);
        $tracker->method('hasSemanticsStatus')->willReturn(true);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);

        $additional_button_action = $this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user);
        self::assertNotNull($additional_button_action);
        self::assertTrue($additional_button_action->getLinkPresenter()->is_disabled);
        self::assertStringContainsString("Title semantic is not defined", $additional_button_action->getLinkPresenter()->disabled_messages);
        self::assertStringNotContainsString("Status semantic is not defined", $additional_button_action->getLinkPresenter()->disabled_messages);
    }

    public function testDisabledActionIsBuiltWhenStatusIsNotDefined(): void
    {
        $source_information                  = new TopBacklogActionArtifactSourceInformation(888, 140, 102);
        $this->build_program                 = BuildProgramStub::stubValidProgram();
        $this->verify_is_in_top_backlog_stub = VerifyIsInTopBacklogStub::buildNotInBacklog();
        $this->verify_feature_planned        = VerifyFeaturePlannedStub::isNotPlanned();
        $tracker                             = $this->createMock(\Tracker::class);
        $tracker->method('hasSemanticsTitle')->willReturn(true);
        $tracker->method('hasSemanticsStatus')->willReturn(false);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);

        $additional_button_action = $this->getBuilder(VerifyPrioritizeFeaturesPermissionStub::canPrioritize())->buildTopBacklogActionBuilder($source_information, $this->user);
        self::assertNotNull($additional_button_action);
        self::assertTrue($additional_button_action->getLinkPresenter()->is_disabled);
        self::assertNotNull($additional_button_action->getLinkPresenter()->disabled_messages);
        self::assertStringContainsString("Status semantic is not defined", $additional_button_action->getLinkPresenter()->disabled_messages);
        self::assertStringNotContainsString("Title semantic is not defined", $additional_button_action->getLinkPresenter()->disabled_messages);
    }

    private function getBuilder(VerifyPrioritizeFeaturesPermission $prioritize_features_permission_verifier): ArtifactTopBacklogActionBuilder
    {
        return new ArtifactTopBacklogActionBuilder(
            $this->build_program,
            $prioritize_features_permission_verifier,
            $this->verify_is_plannable,
            $this->verify_is_in_top_backlog_stub,
            $this->verify_feature_planned,
            new JavascriptAsset(IncludeAssetsBuilder::build(), 'action.js'),
            new TrackerSemantics($this->tracker_factory),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveFullProjectStub::withProject(
                ProjectTestBuilder::aProject()->build()
            )
        );
    }

    private function mockAValidTracker(): void
    {
        $tracker = $this->createStub(\Tracker::class);
        $tracker->method('hasSemanticsTitle')->willReturn(true);
        $tracker->method('hasSemanticsStatus')->willReturn(true);
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
    }
}
