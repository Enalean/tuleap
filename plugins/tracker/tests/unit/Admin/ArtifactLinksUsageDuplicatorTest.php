<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Admin;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinksUsageDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ArtifactLinksUsageDao&MockObject $dao;
    private ArtifactLinksUsageDuplicator $duplicator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->dao        = $this->createMock(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);
        $this->duplicator = new ArtifactLinksUsageDuplicator($this->dao);
    }

    public function testItActivatesTheArtifactLinkTypesIfTemplateAlreadyUseThem(): void
    {
        $template = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccessPrivate()
            ->withUsedService('plugin_tracker')
            ->build();
        $project  = ProjectTestBuilder::aProject()
            ->withId(102)
            ->withAccessPrivate()
            ->withUsedService('plugin_tracker')
            ->build();

        $this->dao->method('isProjectUsingArtifactLinkTypes')->with(101)->willReturn(true);
        $this->dao->expects($this->once())->method('duplicate')->with(101, 102);

        $this->duplicator->duplicate($template, $project);
    }

    public function testItActivatesTheArtifactLinkTypesIfTemplateDoesNotUseTrackerServiceAndNewProjectUseIt(): void
    {
        $template = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccessPrivate()
            ->withoutServices()
            ->build();
        $project  = ProjectTestBuilder::aProject()
            ->withId(102)
            ->withAccessPrivate()
            ->withUsedService('plugin_tracker')
            ->build();

        $this->dao->method('isProjectUsingArtifactLinkTypes')->with(101)->willReturn(false);

        $this->dao->expects($this->once())->method('duplicate')->with(101, 102);

        $this->duplicator->duplicate($template, $project);
    }

    public function testItDoesNotActivateTheArtifactLinkTypesIfTemplateDoesNotUseIt(): void
    {
        $template = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccessPrivate()
            ->withUsedService('plugin_tracker')
            ->build();
        $project  = ProjectTestBuilder::aProject()
            ->withId(102)
            ->withAccessPrivate()
            ->withUsedService('plugin_tracker')
            ->build();

        $this->dao->method('isProjectUsingArtifactLinkTypes')->with(101)->willReturn(false);

        $this->dao->expects($this->never())->method('duplicate')->with(101, 102);

        $this->duplicator->duplicate($template, $project);
    }

    public function testItDoesNotActivateTheArtifactLinkTypesIfTemplateAndNewProjectDoesNotUseTheService(): void
    {
        $template = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccessPrivate()
            ->withoutServices()
            ->build();
        $project  = ProjectTestBuilder::aProject()
            ->withId(102)
            ->withAccessPrivate()
            ->withoutServices()
            ->build();

        $this->dao->method('isProjectUsingArtifactLinkTypes')->with(101)->willReturn(false);

        $this->dao->expects($this->never())->method('duplicate')->with(101, 102);

        $this->duplicator->duplicate($template, $project);
    }
}
