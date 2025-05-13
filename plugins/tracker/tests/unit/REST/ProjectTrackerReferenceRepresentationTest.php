<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\ProjectBackground\ProjectBackgroundName;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectTrackerReferenceRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectBackgroundConfiguration&MockObject $project_background_configuration;

    protected function setUp(): void
    {
        $this->project_background_configuration = $this->createMock(ProjectBackgroundConfiguration::class);
    }

    public function testBuildsWhenProjectHasABackground(): void
    {
        $background_identifier = 'brown-alpaca';
        $this->project_background_configuration->method('getBackground')->willReturn(ProjectBackgroundName::fromIdentifier($background_identifier));

        $representation = ProjectReferenceWithBackground::fromProject(
            ProjectTestBuilder::aProject()->build(),
            $this->project_background_configuration
        );

        self::assertNotNull($representation->background);
        self::assertSame($background_identifier, $representation->background->identifier);
    }

    public function testBuildsWhenProjectDoesNotHaveABackground(): void
    {
        $this->project_background_configuration->method('getBackground')->willReturn(null);

        $representation = ProjectReferenceWithBackground::fromProject(
            ProjectTestBuilder::aProject()->build(),
            $this->project_background_configuration
        );

        self::assertNull($representation->background);
    }
}
