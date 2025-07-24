<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\TemplateRendererStub;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Hierarchy\SearchParentTrackerStub;
use Tuleap\Tracker\Test\Stub\Permission\RetrieveUserPermissionOnTrackersStub;
use Tuleap\Tracker\Test\Stub\RetrieveAllUsableTypesInProjectStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

#[DisableReturnValueGenerationForTestDoubles]
final class ArtifactLinkFieldRendererTest extends TestCase
{
    private ArtifactLinkFieldRenderer $renderer;

    #[\Override]
    protected function setUp(): void
    {
        $parent_tracker    = TrackerTestBuilder::aTracker()->withId(456)->build();
        $presenter_builder = new EditorWithReverseLinksPresenterBuilder(
            new ParentInHierarchyRetriever(
                SearchParentTrackerStub::withParentTracker($parent_tracker->getId()),
                RetrieveTrackerStub::withTrackers($parent_tracker),
            ),
            RetrieveUserPermissionOnTrackersStub::build()->withPermissionOn(
                [$parent_tracker->getId()],
                TrackerPermissionType::PERMISSION_VIEW
            ),
            RetrieveAllUsableTypesInProjectStub::withUsableTypes(new TypeIsChildPresenter()),
        );
        $this->renderer    = new ArtifactLinkFieldRenderer(new TemplateRendererStub(), $presenter_builder);
    }

    public function testItRendersWithArtifact(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(35451)->build();
        $field    = ArtifactLinkFieldBuilder::anArtifactLinkField(8645)->build();

        self::assertIsString($this->renderer->render($field, $artifact, UserTestBuilder::buildWithDefaults()));
    }

    public function testItRendersWithoutArtifact(): void
    {
        $field = ArtifactLinkFieldBuilder::anArtifactLinkField(8645)
            ->inTracker(
                TrackerTestBuilder::aTracker()
                    ->withId(45)
                    ->withProject(ProjectTestBuilder::aProject()->build())
                    ->build()
            )
            ->build();

        self::assertIsString($this->renderer->render($field, null, UserTestBuilder::buildWithDefaults()));
    }
}
