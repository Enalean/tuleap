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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\View\Reference;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Reference\CrossReferenceByDirectionPresenter;
use Tuleap\Reference\CrossReferenceByDirectionPresenterBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class CrossReferenceFieldPresenterBuilderTest extends TestCase
{
    public function testItBuildsThePresenter(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $by_direction_builder = $this->createMock(CrossReferenceByDirectionPresenterBuilder::class);

        $by_direction_presenter = new CrossReferenceByDirectionPresenter([], []);
        $by_direction_builder->method('build')->with('123', 'plugin_tracker_artifact', 102, $user)->willReturn($by_direction_presenter);

        $builder = new CrossReferenceFieldPresenterBuilder($by_direction_builder);

        $artifact = ArtifactTestBuilder::anArtifact(123)
            ->inTracker(
                TrackerTestBuilder::aTracker()
                    ->withProject(ProjectTestBuilder::aProject()->withId(102)->build())
                    ->withShortName('art')
                    ->build()
            )
            ->build();

        $presenter = $builder->build(true, $artifact, $user);
        self::assertEquals($by_direction_presenter, $presenter->by_direction);
        self::assertTrue($presenter->can_delete);
        self::assertEquals('art #123', $presenter->artifact_xref);
        self::assertFalse($presenter->has_cross_refs_to_display);
    }
}
