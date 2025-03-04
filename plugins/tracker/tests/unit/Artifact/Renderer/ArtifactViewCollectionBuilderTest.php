<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Renderer;

use Codendi_Request;
use Tracker_Artifact_EditRenderer;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactViewCollectionBuilderTest extends TestCase
{
    use ForgeConfigSandbox;

    private \EventManager&\PHPUnit\Framework\MockObject\MockObject $event_manager;
    private TypeIsChildLinkRetriever&\PHPUnit\Framework\MockObject\MockObject $retriever;
    private ArtifactViewCollectionBuilder $builder;
    private \Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker;

    protected function setUp(): void
    {
        $this->tracker = $this->createMock(\Tracker::class);
        $this->tracker->method('getId')->willReturn(123);

        $this->event_manager = $this->createMock(\EventManager::class);
        $this->retriever     = $this->createMock(TypeIsChildLinkRetriever::class);

        $this->builder = new ArtifactViewCollectionBuilder($this->event_manager, $this->retriever);
    }

    public function testItAddsTabWhenThereIsArtifactLinksForProjectAllowedToUseType(): void
    {
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->retriever->method('getChildren')->willReturn([ArtifactTestBuilder::anArtifact(2)->build()]);
        $this->event_manager->expects($this->once())->method('processEvent');

        $view_collection = $this->builder->build($artifact, $this->tracker, new Codendi_Request([]), UserTestBuilder::anActiveUser()->build(), $this->createMock(Tracker_Artifact_EditRenderer::class));
        self::assertCount(2, $view_collection->views);
        self::assertSame('edit', $view_collection->views['edit']->getIdentifier());
        self::assertSame('artifact-links', $view_collection->views['artifact-links']->getIdentifier());
    }

    public function testItAddsChildrenTabWhenFeatureFlagIsSet(): void
    {
        \ForgeConfig::set('feature_flag_reactivate_tab_children_in_artifact_view', '1');

        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->retriever->method('getChildren')->willReturn([ArtifactTestBuilder::anArtifact(2)->build()]);
        $this->event_manager->expects($this->once())->method('processEvent');

        $view_collection = $this->builder->build($artifact, $this->tracker, new Codendi_Request([]), UserTestBuilder::anActiveUser()->build(), $this->createMock(Tracker_Artifact_EditRenderer::class));
        self::assertCount(3, $view_collection->views);
        self::assertSame('edit', $view_collection->views['edit']->getIdentifier());
        self::assertSame('link', $view_collection->views['link']->getIdentifier());
        self::assertSame('artifact-links', $view_collection->views['artifact-links']->getIdentifier());
    }

    public function testItDoesNothingWhenThereIsNoArtifactLinksForProjectAllowedToUseType(): void
    {
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(true);
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->retriever->method('getChildren')->willReturn([]);
        $this->event_manager->expects($this->once())->method('processEvent');

        $view_collection = $this->builder->build($artifact, $this->tracker, new Codendi_Request([]), UserTestBuilder::anActiveUser()->build(), $this->createMock(Tracker_Artifact_EditRenderer::class));
        self::assertCount(2, $view_collection->views);
        self::assertSame('edit', $view_collection->views['edit']->getIdentifier());
        self::assertSame('artifact-links', $view_collection->views['artifact-links']->getIdentifier());
    }

    public function testWhenProjectDoesNotUseTypedLinkTabIsAddedWhenTrackerHasChildrenInHierarchy(): void
    {
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $this->tracker->method('getChildren')->willReturn(TrackerTestBuilder::aTracker()->build());
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->event_manager->expects($this->once())->method('processEvent');

        $view_collection = $this->builder->build($artifact, $this->tracker, new Codendi_Request([]), UserTestBuilder::anActiveUser()->build(), $this->createMock(Tracker_Artifact_EditRenderer::class));
        self::assertCount(2, $view_collection->views);
        self::assertSame('edit', $view_collection->views['edit']->getIdentifier());
        self::assertSame('artifact-links', $view_collection->views['artifact-links']->getIdentifier());
    }

    public function testWhenProjectDoesNotUseTypedLinkNoTabIsAddedWhenTrackerHasNoChildrenInHierarchy(): void
    {
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $this->tracker->method('getChildren')->willReturn([]);
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->event_manager->expects($this->once())->method('processEvent');

        $view_collection = $this->builder->build($artifact, $this->tracker, new Codendi_Request([]), UserTestBuilder::anActiveUser()->build(), $this->createMock(Tracker_Artifact_EditRenderer::class));
        self::assertCount(2, $view_collection->views);
        self::assertSame('edit', $view_collection->views['edit']->getIdentifier());
        self::assertSame('artifact-links', $view_collection->views['artifact-links']->getIdentifier());
    }

    public function testItAlwaysAddsArtifactLinkTab(): void
    {
        $this->tracker->method('isProjectAllowedToUseType')->willReturn(false);
        $this->tracker->method('getChildren')->willReturn([]);
        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->event_manager->expects($this->once())->method('processEvent');

        $view_collection = $this->builder->build($artifact, $this->tracker, new Codendi_Request([]), UserTestBuilder::anActiveUser()->build(), $this->createMock(Tracker_Artifact_EditRenderer::class));
        self::assertCount(2, $view_collection->views);
        self::assertSame('edit', $view_collection->views['edit']->getIdentifier());
        self::assertSame('artifact-links', $view_collection->views['artifact-links']->getIdentifier());
    }
}
