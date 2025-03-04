<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Codendi_Request;
use EventManager;
use PFUser;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tracker;
use Tracker_Action_UpdateArtifact;
use Tracker_Artifact_Redirect;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElementFactory;
use Tracker_HierarchyFactory;
use Tracker_IDisplayTrackerLayout;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_Action_UpdateArtifactTest extends TestCase
{
    use GlobalResponseMock;

    private const ARTIFACT_ID = 123;
    private const TRACKER_ID  = 101;

    private Artifact&MockObject $task;
    private Artifact&MockObject $user_story;
    private Tracker_FormElement_Field_Computed&MockObject $computed_field;
    private Tracker_FormElement_Field_Computed&MockObject $us_computed_field;
    private ?string $old_request_with = null;
    private TypeIsChildLinkRetriever&MockObject $artifact_retriever;
    private Tracker_IDisplayTrackerLayout&MockObject $layout;
    private Codendi_Request $request;
    private PFUser $user;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private Tracker_HierarchyFactory&MockObject $hierarchy_factory;
    private EventManager&MockObject $event_manager;
    private Tracker_Action_UpdateArtifact $action;
    private HiddenFieldsetsDetector&MockObject $hidden_fieldsets_detector;

    protected function setUp(): void
    {
        $tracker_user_story_id = 103;
        $tracker               = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('augmentDataFromRequest');
        $tracker->method('getItemName');
        $tracker->method('hasFormElementWithNameAndType');
        $this->layout              = $this->createMock(Tracker_IDisplayTrackerLayout::class);
        $this->request             = new Codendi_Request(['func' => 'artifact-update'], $this->createMock(ProjectManager::class));
        $this->user                = UserTestBuilder::buildWithDefaults();
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->hierarchy_factory   = $this->createMock(Tracker_HierarchyFactory::class);
        $this->computed_field      = $this->createMock(Tracker_FormElement_Field_Computed::class);
        $this->us_computed_field   = $this->createMock(Tracker_FormElement_Field_Computed::class);
        $this->user_story          = $this->createMock(Artifact::class);
        $tracker_user_story        = $this->createMock(Tracker::class);
        $tracker_user_story->method('getId')->willReturn($tracker_user_story_id);
        $tracker_user_story->method('hasFormElementWithNameAndType');

        $this->user_story->method('getTrackerId')->willReturn($tracker_user_story_id);
        $this->user_story->method('getTracker')->willReturn($tracker_user_story);
        $this->user_story->method('getId')->willReturn(107);

        $this->task = $this->createPartialMock(Artifact::class, ['createNewChangeset']);
        $this->task->setHierarchyFactory($this->hierarchy_factory);
        $this->task->setTracker($tracker);
        $this->task->setFormElementFactory($this->formelement_factory);
        $this->task->setId(self::ARTIFACT_ID);
        $this->task->method('createNewChangeset')->willReturn(true);

        $this->computed_field->method('getName')->willReturn(Tracker::REMAINING_EFFORT_FIELD_NAME);
        $this->us_computed_field->method('fetchCardValue')->with($this->user_story)->willReturn(23);
        $this->us_computed_field->method('getName')->willReturn(Tracker::REMAINING_EFFORT_FIELD_NAME);

        $this->event_manager             = $this->createMock(EventManager::class);
        $this->artifact_retriever        = $this->createMock(TypeIsChildLinkRetriever::class);
        $this->hidden_fieldsets_detector = $this->createMock(HiddenFieldsetsDetector::class);

        $this->action = new Tracker_Action_UpdateArtifact(
            $this->task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $this->createMock(VisitRecorder::class),
            $this->hidden_fieldsets_detector
        );
    }

    private function setUpAjaxRequestHeaders(): void
    {
        $this->old_request_with           = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
    }

    #[After]
    protected function restoreAjaxRequestHeaders(): void
    {
        if ($this->old_request_with === null) {
            unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        } else {
            $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->old_request_with;
        }
    }

    public function testItDoesNotSendAnythingIfNoRemainingEffortFieldIsDefinedOnTask(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $this->formelement_factory->method('getComputableFieldByNameForUser')->with(self::TRACKER_ID, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->willReturn(null);

        $expected = [];
        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendsParentsRemainingEffortEvenIfTaskDontHaveOne(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($this->user_story);
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => null,
                103              => $this->us_computed_field,
            }
        );

        $user_story_id = $this->user_story->getId();
        $expected      = [$user_story_id => ['remaining_effort' => 23]];
        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNotSendParentWhenParentHasNoRemainingEffortField(): void
    {
        $this->setUpAjaxRequestHeaders();
        $tracker_user_story = TrackerTestBuilder::aTracker()->withId(110)->build();
        $user_story         = ArtifactTestBuilder::anArtifact(111)->inTracker($tracker_user_story)->build();

        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($user_story);
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturn(null);

        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with([]);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendTheAutocomputedValueOfTheArtifact(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->with(self::TRACKER_ID, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->willReturn($this->computed_field);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('augmentDataFromRequest');
        $task = $this->createMock(Artifact::class);
        $task->method('getId')->willReturn(self::ARTIFACT_ID);
        $task->method('getTracker')->willReturn($tracker);
        $task->method('validateCommentFormat')->willReturn(CommentFormatIdentifier::COMMONMARK);
        $task->method('createNewChangeset');
        $task->method('fetchDirectLinkToArtifact');
        $task->method('summonArtifactRedirectors');
        $task->method('getParent');
        $visit_recorder = $this->createMock(VisitRecorder::class);

        $action = new Tracker_Action_UpdateArtifact(
            $task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $visit_recorder,
            $this->hidden_fieldsets_detector
        );

        $this->computed_field->method('getName')->willReturn(Tracker::REMAINING_EFFORT_FIELD_NAME);
        $task->method('getTracker')->willReturn($tracker);
        $this->computed_field->method('fetchCardValue')->with($task)->willReturn(42);
        $tracker->method('hasFormElementWithNameAndType')->willReturn(true);
        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(true);

        $expected = [
            self::ARTIFACT_ID => ['remaining_effort' => '42 (autocomputed)'],
        ];
        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with($expected);

        $action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendsTheRemainingEffortOfTheArtifactAndItsParent(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => $this->computed_field,
                103              => $this->us_computed_field,
            }
        );
        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(false);
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($this->user_story);
        $this->computed_field->method('fetchCardValue')->with($this->task)->willReturn(42);

        $user_story_id = $this->user_story->getId();
        $expected      = [
            self::ARTIFACT_ID => ['remaining_effort' => 42],
            $user_story_id    => ['remaining_effort' => 23],
        ];
        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNotSendParentsRemainingEffortWhenThereIsNoParent(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->with(self::TRACKER_ID, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->willReturn($this->computed_field);
        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(false);
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $this->computed_field->method('fetchCardValue')->with($this->task)->willReturn(42);

        $expected = [
            self::ARTIFACT_ID => ['remaining_effort' => 42],
        ];
        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testSendCardInfoOnUpdateWithRemainingEffortItDoesNotSendParentWhenParentHasNoRemainingEffortField(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => $this->computed_field,
                110              => null,
            }
        );
        $tracker_user_story = TrackerTestBuilder::aTracker()->withId(110)->build();
        $user_story         = ArtifactTestBuilder::anArtifact(111)->inTracker($tracker_user_story)->build();

        $this->computed_field->method('isArtifactValueAutocomputed')->willReturn(false);
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($user_story);
        $this->computed_field->method('fetchCardValue')->with($this->task)->willReturn(42);

        $expected = [
            self::ARTIFACT_ID => ['remaining_effort' => 42],
        ];
        $GLOBALS['Response']->expects(self::once())->method('sendJSON')->with($expected);

        $this->action->process($this->layout, $this->request, $this->user);
    }

    private function getProcessAndCaptureOutput(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $user): string
    {
        ob_start();
        $this->action->process($layout, $request, $user);
        return ob_get_clean();
    }

    public function testItCreatesAChangeset(): void
    {
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], $this->createMock(ProjectManager::class));

        $this->task->expects(self::once())->method('createNewChangeset')->willReturn(true);

        $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
    }

    public function testItReturnsTheScriptTagIfRequestIsFromOverlay(): void
    {
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn($this->user_story);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], $this->createMock(ProjectManager::class));

        $from_overlay = $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
        $expected     = '<script type="text/javascript" nonce="">window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition(' . $this->task->getId() . ');</script>';
        self::assertSame($expected, $from_overlay);
    }

    public function testItDoesntReturnScriptWhenInAjax(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->method('getComputableFieldByNameForUser')->willReturnCallback(
            fn(int $tracker_id) => match ($tracker_id) {
                self::TRACKER_ID => null,
                103              => $this->us_computed_field,
            }
        );
        $this->hierarchy_factory->method('getParentArtifact')->with($this->user, $this->task)->willReturn(null);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], $this->createMock(ProjectManager::class));

        $from_overlay = $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
        $this->assertStringNotContainsStringIgnoringCase('<script>', $from_overlay);
    }

    private function getRedirectUrlFor(array $request_data): Tracker_Artifact_Redirect
    {
        $request = new Codendi_Request($request_data, $this->createMock(ProjectManager::class));
        $action  = new class (
            $this->task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $this->createMock(VisitRecorder::class),
            $this->hidden_fieldsets_detector
        ) extends Tracker_Action_UpdateArtifact {
            public function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request)
            {
                return parent::getRedirectUrlAfterArtifactUpdate($request);
            }
        };
        return $action->getRedirectUrlAfterArtifactUpdate($request);
    }

    public function testItRedirectsToTheTrackerHomePageByDefault(): void
    {
        $request_data = [];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEquals(TRACKER_BASE_URL . '/?tracker=' . self::TRACKER_ID, $redirect_uri->toUrl());
    }

    public function testItStaysOnTheCurrentArtifactWhenSubmitAndStayIsSpecified(): void
    {
        $request_data = ['submit_and_stay' => true];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEquals(TRACKER_BASE_URL . '/?aid=' . self::ARTIFACT_ID, $redirect_uri->toUrl());
    }

    public function testItReturnsToThePreviousArtifactWhenFromAidIsGiven(): void
    {
        $from_aid     = 33;
        $request_data = ['from_aid' => $from_aid];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEquals(TRACKER_BASE_URL . "/?aid=$from_aid", $redirect_uri->toUrl());
    }

    private function assertURIHasArgument(string $url, string $argument, string $argument_value): void
    {
        $query_string = parse_url($url, PHP_URL_QUERY);
        parse_str($query_string, $args);
        $this->assertTrue(isset($args[$argument]));
        $this->assertEquals($argument_value, $args[$argument]);
    }

    public function testSubmitAndStayHasPrecedenceOverFromAid(): void
    {
        $from_aid     = 33;
        $request_data = [
            'from_aid'        => $from_aid,
            'submit_and_stay' => true,
        ];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'aid', (string) self::ARTIFACT_ID);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'from_aid', (string) $from_aid);
    }

    public function testSubmitAndStayHasPrecedenceOverReturnToAid(): void
    {
        $request_data = ['submit_and_stay' => true];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'aid', (string) self::ARTIFACT_ID);
    }
}
