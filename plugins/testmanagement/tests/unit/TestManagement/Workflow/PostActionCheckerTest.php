<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Workflow;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\CheckPostActionsForTracker;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;
use Tuleap\Tracker\Workflow\PostAction\Update\PostActionCollection;

final class PostActionCheckerTest extends TestCase
{
    private const TRACKER_ID = 101;
    private \PHPUnit\Framework\MockObject\MockObject|Config $config;
    private \Tracker_FormElementFactory|\PHPUnit\Framework\MockObject\MockObject $form_element_factory;
    private PostActionChecker $checker;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->config               = $this->createMock(Config::class);
        $this->form_element_factory = $this->createMock(\Tracker_FormElementFactory::class);

        $this->checker = new PostActionChecker($this->config, $this->form_element_factory);

        $this->tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();
    }

    public function testItDoesNothingWhenNoPostAction(): void
    {
        $event = new CheckPostActionsForTracker($this->tracker, new PostActionCollection());
        $this->checker->checkPostActions($event);

        self::assertTrue($event->arePostActionsEligible());
    }

    public function testItDoesNothingWhenTrackerAreOutsideTestManagement(): void
    {
        $this->config->method('getTestExecutionTrackerId')->willReturn(1);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(2);
        $event = new CheckPostActionsForTracker($this->tracker, new PostActionCollection());
        $this->checker->checkPostActions($event);

        self::assertTrue($event->arePostActionsEligible());
    }

    public function testItBlocksPostActionForHiddenFieldsetWhenTrackerIsUsedInTestManagement(): void
    {
        $this->config->method('getTestExecutionTrackerId')->willReturn(self::TRACKER_ID);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(2);
        $collection = $this->createMock(PostActionCollection::class);
        $event      = new CheckPostActionsForTracker($this->tracker, $collection);

        $collection->method('getFrozenFieldsPostActions')->willReturn([]);
        $collection->method('getHiddenFieldsetsPostActions')->willReturn([new HiddenFieldsetsValue([123, 456])]);
        $this->checker->checkPostActions($event);

        self::assertFalse($event->arePostActionsEligible());
        self::assertStringContainsString(
            '"hidden fieldsets" are defined',
            $event->getErrorMessage()
        );
    }

    public function testItBlocksPostActionForFrozenFieldForArtifactLinkFieldOfExecutionTracker(): void
    {
        $this->config->method('getTestExecutionTrackerId')->willReturn(self::TRACKER_ID);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(2);
        $collection = $this->createMock(PostActionCollection::class);
        $event      = new CheckPostActionsForTracker($this->tracker, $collection);

        $collection->method('getFrozenFieldsPostActions')->willReturn([new FrozenFieldsValue([123])]);
        $collection->method('getHiddenFieldsetsPostActions')->willReturn([]);

        $this->form_element_factory->method('getFieldById')->willReturn(
            ArtifactLinkFieldBuilder::anArtifactLinkField(1412)->withTrackerId(self::TRACKER_ID)->build()
        );
        $this->checker->checkPostActions($event);

        self::assertFalse($event->arePostActionsEligible());
        self::assertStringContainsString(
            '"frozen fields" are defined on an artifact link field',
            $event->getErrorMessage()
        );
    }

    public function testItBlocksPostActionForFrozenFieldForOtherFieldOfExecutionTracker(): void
    {
        $this->config->method('getTestExecutionTrackerId')->willReturn(self::TRACKER_ID);
        $this->config->method('getTestDefinitionTrackerId')->willReturn(2);
        $collection = $this->createMock(PostActionCollection::class);
        $event      = new CheckPostActionsForTracker($this->tracker, $collection);

        $collection->method('getFrozenFieldsPostActions')->willReturn([new FrozenFieldsValue([123])]);
        $collection->method('getHiddenFieldsetsPostActions')->willReturn([]);

        $this->form_element_factory->method('getFieldById')->willReturn(
            new \Tracker_FormElement_Field_Text(1412, self::TRACKER_ID, 1000, 'artifact link', 'Links', 'Irrelevant', true, 'P', false, '', 3)
        );
        $this->checker->checkPostActions($event);

        self::assertTrue($event->arePostActionsEligible());
    }
}
