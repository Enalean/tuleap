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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
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
use Tuleap\Tracker\TrackerColor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EditorWithReverseLinksPresenterBuilderTest extends TestCase
{
    private const LINK_FIELD_ID             = 865;
    private const LINK_FIELD_LABEL          = 'My Artifact Links';
    private const CURRENT_ARTIFACT_ID       = 891;
    private const CURRENT_TRACKER_ID        = 611;
    private const CURRENT_TRACKER_COLOR     = 'deep-blue';
    private const CURRENT_TRACKER_SHORTNAME = 'story';
    private const PARENT_TRACKER_ID         = 487;
    private const CURRENT_PROJECT_ID        = 565;
    private SearchParentTrackerStub $search_parent_tracker;
    private RetrieveUserPermissionOnTrackersStub $tracker_permissions_retriever;

    protected function setUp(): void
    {
        $this->search_parent_tracker         = SearchParentTrackerStub::withParentTracker(self::PARENT_TRACKER_ID);
        $this->tracker_permissions_retriever = RetrieveUserPermissionOnTrackersStub::build()->withPermissionOn(
            [self::PARENT_TRACKER_ID],
            TrackerPermissionType::PERMISSION_VIEW
        );
    }

    private function build(): EditorWithReverseLinksPresenter
    {
        $current_project = ProjectTestBuilder::aProject()->withId(self::CURRENT_PROJECT_ID)->build();

        $parent_tracker  = TrackerTestBuilder::aTracker()->withId(self::PARENT_TRACKER_ID)
            ->withProject($current_project)
            ->build();
        $current_tracker = TrackerTestBuilder::aTracker()->withId(self::CURRENT_TRACKER_ID)
            ->withColor(TrackerColor::fromName(self::CURRENT_TRACKER_COLOR))
            ->withShortName(self::CURRENT_TRACKER_SHORTNAME)
            ->withProject($current_project)
            ->build();

        $current_artifact = ArtifactTestBuilder::anArtifact(self::CURRENT_ARTIFACT_ID)
            ->inTracker($current_tracker)
            ->build();

        $link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(self::LINK_FIELD_ID)
            ->withLabel(self::LINK_FIELD_LABEL)
            ->build();

        $builder = new EditorWithReverseLinksPresenterBuilder(
            new ParentInHierarchyRetriever(
                $this->search_parent_tracker,
                RetrieveTrackerStub::withTrackers($parent_tracker),
            ),
            $this->tracker_permissions_retriever,
            RetrieveAllUsableTypesInProjectStub::withUsableTypes(new TypeIsChildPresenter()),
        );
        return $builder->buildWithArtifact($link_field, $current_artifact, UserTestBuilder::buildWithDefaults());
    }

    public function testItBuilds(): void
    {
        $presenter = $this->build();
        self::assertSame(self::LINK_FIELD_ID, $presenter->link_field_id);
        self::assertSame(self::LINK_FIELD_LABEL, $presenter->link_field_label);
        self::assertSame(self::CURRENT_ARTIFACT_ID, $presenter->current_artifact_id);
        self::assertSame(self::CURRENT_TRACKER_ID, $presenter->current_tracker_id);
        self::assertSame(self::CURRENT_TRACKER_COLOR, $presenter->current_tracker_color);
        self::assertSame(self::CURRENT_TRACKER_SHORTNAME, $presenter->current_tracker_short_name);
        self::assertSame(self::PARENT_TRACKER_ID, $presenter->parent_tracker_id);
        self::assertSame(self::CURRENT_PROJECT_ID, $presenter->current_project_id);
        self::assertSame('[{"reverse_label":"Parent","forward_label":"Child","shortname":"_is_child","is_system":true,"is_visible":true}]', $presenter->allowed_link_types);
    }

    public function testItBuildsWithoutArtifact(): void
    {
        $current_project = ProjectTestBuilder::aProject()->withId(self::CURRENT_PROJECT_ID)->build();

        $parent_tracker  = TrackerTestBuilder::aTracker()->withId(self::PARENT_TRACKER_ID)
            ->withProject($current_project)
            ->build();
        $current_tracker = TrackerTestBuilder::aTracker()->withId(self::CURRENT_TRACKER_ID)
            ->withColor(TrackerColor::fromName(self::CURRENT_TRACKER_COLOR))
            ->withShortName(self::CURRENT_TRACKER_SHORTNAME)
            ->withProject($current_project)
            ->build();

        $link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(self::LINK_FIELD_ID)
            ->withLabel(self::LINK_FIELD_LABEL)
            ->inTracker($current_tracker)
            ->build();

        $builder   = new EditorWithReverseLinksPresenterBuilder(
            new ParentInHierarchyRetriever(
                $this->search_parent_tracker,
                RetrieveTrackerStub::withTrackers($parent_tracker),
            ),
            $this->tracker_permissions_retriever,
            RetrieveAllUsableTypesInProjectStub::withUsableTypes(new TypeIsChildPresenter()),
        );
        $presenter = $builder->buildWithoutArtifact($link_field, UserTestBuilder::buildWithDefaults());
        self::assertSame(self::LINK_FIELD_ID, $presenter->link_field_id);
        self::assertSame(self::LINK_FIELD_LABEL, $presenter->link_field_label);
        self::assertNull($presenter->current_artifact_id);
        self::assertSame(self::CURRENT_TRACKER_ID, $presenter->current_tracker_id);
        self::assertSame(self::CURRENT_TRACKER_COLOR, $presenter->current_tracker_color);
        self::assertSame(self::CURRENT_TRACKER_SHORTNAME, $presenter->current_tracker_short_name);
        self::assertSame(self::PARENT_TRACKER_ID, $presenter->parent_tracker_id);
        self::assertSame(self::CURRENT_PROJECT_ID, $presenter->current_project_id);
        self::assertSame('[{"reverse_label":"Parent","forward_label":"Child","shortname":"_is_child","is_system":true,"is_visible":true}]', $presenter->allowed_link_types);
    }

    public function testItBuildsWithoutHierarchy(): void
    {
        $this->search_parent_tracker = SearchParentTrackerStub::withNoParent();

        $presenter = $this->build();
        self::assertNull($presenter->parent_tracker_id);
    }

    public function testItBuildsWhenUserCannotSeeParentTracker(): void
    {
        $this->tracker_permissions_retriever = RetrieveUserPermissionOnTrackersStub::build();

        $presenter = $this->build();
        self::assertNull($presenter->parent_tracker_id);
    }
}
