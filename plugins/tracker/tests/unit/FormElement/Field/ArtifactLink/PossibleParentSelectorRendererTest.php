<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tuleap\ForgeConfigSandbox;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PossibleParentSelectorRendererTest extends TestCase
{
    use ForgeConfigSandbox;
    use TemporaryTestDirectory;

    private \PFUser $user;
    private \Tracker $user_story_tracker;
    private PossibleParentSelectorRenderer $renderer;

    protected function setUp(): void
    {
        $this->user               = UserTestBuilder::aUser()->build();
        $this->user_story_tracker = TrackerTestBuilder::aTracker()->withId(35)->build();
        $GLOBALS['HTML']          = LayoutBuilder::build();

        \ForgeConfig::set('codendi_cache_dir', $this->getTmpDir());
        $this->renderer = PossibleParentSelectorRenderer::buildWithDefaultTemplateRenderer();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['HTML']);
    }

    public function testItProposeToCreateNewArtifactByDefault(): void
    {
        $possible_parent_selector = new PossibleParentSelector($this->user, $this->user_story_tracker, 0, 0);

        $xml = simplexml_load_string($this->renderer->render('artifact[155]', '', $possible_parent_selector));

        self::assertEquals('Creation', $xml->select->optgroup['label']);
        self::assertStringStartsWith('Create a new', (string) $xml->select->optgroup->option);
        self::assertEquals((string) \Tracker_FormElement_Field_ArtifactLink::CREATE_NEW_PARENT_VALUE, (string) $xml->select->optgroup->option['value']);
    }

    public function testCanCreateIsDisabled(): void
    {
        $possible_parent_selector = new PossibleParentSelector($this->user, $this->user_story_tracker, 0, 0);
        $possible_parent_selector->disableCreate();

        $xml = simplexml_load_string($this->renderer->render('artifact[155]', '', $possible_parent_selector));

        self::assertCount(0, $xml->select->optgroup);
    }

    public function testItProposeAPossibleParent(): void
    {
        $possible_parent_selector = new PossibleParentSelector($this->user, $this->user_story_tracker, 0, 0);
        $possible_parent_selector->disableCreate();
        $possible_parent_selector->addPossibleParents(
            new \Tracker_Artifact_PaginatedArtifacts(
                [
                    ArtifactTestBuilder::anArtifact(123)
                        ->withTitle('fuu bar')
                        ->inTracker(
                            TrackerTestBuilder::aTracker()
                                ->withId(888)
                                ->withName('epics')
                                ->withProject(
                                    ProjectTestBuilder::aProject()
                                        ->withPublicName('Guinea Pig')
                                        ->build()
                                )
                                ->build()
                        )
                        ->build(),
                ],
                1
            )
        );

        $xml = simplexml_load_string($this->renderer->render('artifact[155]', '', $possible_parent_selector));

        self::assertCount(1, $xml->select->optgroup);
        self::assertEquals('Guinea Pig - open epics', $xml->select->optgroup['label']);
        self::assertCount(1, $xml->select->optgroup->option);
        self::assertEquals('123', (string) $xml->select->optgroup->option[0]['value']);
        self::assertStringContainsString('fuu bar', (string) $xml->select->optgroup->option[0]);
    }

    public function testItProposePossibleParentsInDifferentTrackersAndProjects(): void
    {
        $possible_parent_selector = new PossibleParentSelector($this->user, $this->user_story_tracker, 0, 0);
        $possible_parent_selector->addPossibleParents(
            new \Tracker_Artifact_PaginatedArtifacts(
                [
                    ArtifactTestBuilder::anArtifact(123)
                        ->withTitle('fuu bar')
                        ->inTracker(
                            TrackerTestBuilder::aTracker()
                                ->withId(888)
                                ->withName('epics')
                                ->withProject(
                                    ProjectTestBuilder::aProject()
                                        ->withPublicName('Guinea Pig')
                                        ->build()
                                )
                                ->build()
                        )
                        ->build(),
                    ArtifactTestBuilder::anArtifact(456)
                        ->withTitle('miaou')
                        ->inTracker(
                            TrackerTestBuilder::aTracker()
                                ->withId(777)
                                ->withName('stories')
                                ->withProject(
                                    ProjectTestBuilder::aProject()
                                        ->withPublicName('Cat')
                                        ->build()
                                )
                                ->build()
                        )
                        ->build(),
                ],
                2
            )
        );


        $xml = simplexml_load_string($this->renderer->render('artifact[155]', '', $possible_parent_selector));



        self::assertCount(3, $xml->select->optgroup);
        self::assertEquals('Creation', $xml->select->optgroup[0]['label']);
        self::assertEquals('Guinea Pig - open epics', $xml->select->optgroup[1]['label']);
        self::assertCount(1, $xml->select->optgroup[1]->option);
        self::assertEquals('123', (string) $xml->select->optgroup[1]->option[0]['value']);
        self::assertStringContainsString('fuu bar', (string) $xml->select->optgroup[1]->option[0]);
        self::assertEquals('Cat - open stories', $xml->select->optgroup[2]['label']);
        self::assertCount(1, $xml->select->optgroup[2]->option);
        self::assertEquals('456', (string) $xml->select->optgroup[2]->option[0]['value']);
        self::assertStringContainsString('miaou', (string) $xml->select->optgroup[2]->option[0]);
    }
}
