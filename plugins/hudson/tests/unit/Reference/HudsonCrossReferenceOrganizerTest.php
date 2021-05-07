<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Hudson\Reference;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use ProjectManager;
use Tuleap\Reference\CrossReferenceByNatureOrganizer;
use Tuleap\Test\Builders\CrossReferencePresenterBuilder;

class HudsonCrossReferenceOrganizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HudsonCrossReferenceOrganizer
     */
    private $organizer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|CrossReferenceByNatureOrganizer
     */
    private $organizer_by_nature;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->project = Mockery::mock(\Project::class);
        $this->project->shouldReceive('getID')->andReturn(101);

        $this->organizer_by_nature = Mockery::mock(CrossReferenceByNatureOrganizer::class);
        $this->user                = Mockery::mock(\PFUser::class);

        $this->organizer = new HudsonCrossReferenceOrganizer($this->project_manager);
    }

    public function testItDontMoveCrossReferenceIfNotHudson(): void
    {
        $this->organizer_by_nature
            ->shouldReceive("getCrossReferencePresenters")
            ->andReturn([CrossReferencePresenterBuilder::get(1)->withType('git')->build()]);

        $this->organizer_by_nature
            ->shouldReceive("removeUnreadableCrossReference")
            ->never();

        $this->organizer_by_nature
            ->shouldReceive("moveCrossReferenceToSection")
            ->never();

        $this->organizer->organizeHudsonReferences($this->organizer_by_nature);
    }

    public function testItRemoveHudsonCrossReferenceIfUserIsNotMemberOfProject(): void
    {
        $ref_build = CrossReferencePresenterBuilder::get(1)->withType("hudson_build")->withValue('23')->withProjectId(101)->build();
        $ref_job   = CrossReferencePresenterBuilder::get(1)->withType("hudson_job")->withValue('MyJob')->withProjectId(101)->build();

        $this->organizer_by_nature
            ->shouldReceive("getCrossReferencePresenters")
            ->once()
            ->andReturn([$ref_build, $ref_job]);

        $this->user->shouldReceive('isMember')->with(101)->twice()->andReturn(false);

        $this->organizer_by_nature
            ->shouldReceive("getCurrentUser")
            ->andReturn($this->user);

        $this->project_manager->shouldReceive("getProject")->andReturn($this->project)->twice();

        $this->organizer_by_nature
            ->shouldReceive("removeUnreadableCrossReference")
            ->with($ref_build)
            ->once();

        $this->organizer_by_nature
            ->shouldReceive("removeUnreadableCrossReference")
            ->with($ref_job)
            ->once();

        $this->organizer_by_nature
            ->shouldReceive("moveCrossReferenceToSection")
            ->never();

        $this->organizer->organizeHudsonReferences($this->organizer_by_nature);
    }

    public function testItMoveHudsonCrossReferenceInUnlabelledSection(): void
    {
        $ref_build = CrossReferencePresenterBuilder::get(1)->withType("hudson_build")->withValue('23')->withProjectId(101)->build();
        $ref_job   = CrossReferencePresenterBuilder::get(1)->withType("hudson_job")->withValue('MyJob')->withProjectId(101)->build();


        $this->organizer_by_nature
            ->shouldReceive("getCrossReferencePresenters")
            ->andReturn([$ref_build, $ref_job]);

        $this->user->shouldReceive('isMember')->with(101)->twice()->andReturn(true);

        $this->organizer_by_nature
            ->shouldReceive("getCurrentUser")
            ->andReturn($this->user);

        $this->project_manager->shouldReceive("getProject")->andReturn($this->project)->twice();

        $this->organizer_by_nature
            ->shouldReceive("removeUnreadableCrossReference")
            ->never();

        $this->organizer_by_nature
            ->shouldReceive("moveCrossReferenceToSection")
            ->with($ref_build, "")
            ->once();

        $this->organizer_by_nature
            ->shouldReceive("moveCrossReferenceToSection")
            ->with($ref_job, "")
            ->once();

        $this->organizer->organizeHudsonReferences($this->organizer_by_nature);
    }
}
