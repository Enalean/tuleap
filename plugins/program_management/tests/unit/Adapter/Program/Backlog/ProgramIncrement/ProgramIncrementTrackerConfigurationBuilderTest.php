<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Program\Program;
use Tuleap\ProgramManagement\ProgramTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementTrackerConfigurationBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    /**
     * @var ProgramIncrementTrackerConfigurationBuilder
     */
    private $configuration_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BuildPlanProgramIncrementConfiguration
     */
    private $plan_builder;

    protected function setUp(): void
    {
        $this->plan_builder                 = Mockery::mock(BuildPlanProgramIncrementConfiguration::class);
        $this->tracker_form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);

        $this->configuration_builder = new ProgramIncrementTrackerConfigurationBuilder(
            $this->plan_builder,
            $this->tracker_form_element_factory
        );
    }

    public function testItBuildsAProgramIncrementTrackerConfiguration(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $program_tracker = new ProgramTracker($tracker);
        $this->plan_builder->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn($program_tracker);

        $field = new \Tracker_FormElement_Field_ArtifactLink(
            1,
            101,
            null,
            "artlink",
            "artlink",
            "",
            true,
            "P",
            false,
            false,
            10
        );
        $this->tracker_form_element_factory->shouldReceive('getAnArtifactLinkField')->andReturn($field);

        $user                   = UserTestBuilder::aUser()->build();
        $project                = new Program(101);
        $expected_configuration = new ProgramIncrementTrackerConfiguration($project->getId(), false, $field->getId());

        self::assertEquals($expected_configuration, $this->configuration_builder->build($user, $project));
    }
}
