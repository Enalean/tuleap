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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Action_UpdateArtifactTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;

    /** @var Artifact */
    protected $task;

    /** @var Artifact */
    protected $user_story;

    /** @var int */
    protected $artifact_id = 123;

    /** @var int */
    protected $tracker_id = 101;

    /** @var Tracker_FormElement_Field_Computed */
    protected $computed_field;

    /** @var Tracker_FormElement_Field_Computed */
    protected $us_computed_field;

    protected $old_request_with = null;

    /** @var  NatureIsChildLinkRetriever */
    protected $artifact_retriever;

    /** @var  Tracker_IDisplayTrackerLayout */
    protected $layout;

    protected $request;

    /** @var  PFUser */
    protected $user;

    /** @var  Tracker_FormElementFactory */
    protected $formelement_factory;

    /** @var  Tracker_HierarchyFactory */
    protected $hierarchy_factory;

    /** @var  EventManager */
    protected $event_manager;

    /** @var  Tracker_Action_UpdateArtifact */
    protected $action;

    /** @var HiddenFieldsetsDetector */
    protected $hidden_fieldsets_detector;

    protected function setUp(): void
    {
        parent::setUp();

        $tracker_user_story_id     = 103;
        $user_story_id             = 107;
        $submitted_by              = 102;
        $submitted_on              = 1234567890;
        $use_artifact_permissions  = false;
        $tracker                   = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($this->tracker_id);
        $this->layout              = \Mockery::spy(\Tracker_IDisplayTrackerLayout::class);
        $this->request             = new Codendi_Request(['func' => 'artifact-update'], \Mockery::spy(ProjectManager::class));
        $this->user                = \Mockery::spy(\PFUser::class);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->hierarchy_factory   = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $this->computed_field      = \Mockery::spy(\Tracker_FormElement_Field_Computed::class);
        $this->us_computed_field   = \Mockery::spy(\Tracker_FormElement_Field_Computed::class);
        $this->user_story          = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker_user_story        = Mockery::spy(Tracker::class);
        $tracker_user_story->shouldReceive('getId')->andReturn($tracker_user_story_id);

        $this->user_story->shouldReceive('getTrackerId')->andReturns($tracker_user_story_id);
        $this->user_story->shouldReceive('getTracker')->andReturns($tracker_user_story);
        $this->user_story->shouldReceive('getId')->andReturns($user_story_id);

        $this->task = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->task->setHierarchyFactory($this->hierarchy_factory);
        $this->task->setTracker($tracker);
        $this->task->setFormElementFactory($this->formelement_factory);
        $this->task->setId($this->artifact_id);
        $this->task->shouldReceive('createNewChangeset')->andReturns(true)->byDefault();
        $this->formelement_factory->shouldReceive('getComputableFieldByNameForUser')->with($tracker_user_story_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->andReturns($this->us_computed_field);

        $this->computed_field->shouldReceive('fetchCardValue')->with($this->task)->andReturns(42);
        $this->us_computed_field->shouldReceive('fetchCardValue')->with($this->user_story)->andReturns(23);

        $this->event_manager             = \Mockery::spy(\EventManager::class);
        $this->artifact_retriever        = \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever::class);
        $visit_recorder                  = \Mockery::spy(\Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder::class);
        $this->hidden_fieldsets_detector = \Mockery::spy(HiddenFieldsetsDetector::class);

        $this->action = new Tracker_Action_UpdateArtifact(
            $this->task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $visit_recorder,
            $this->hidden_fieldsets_detector
        );
    }

    private function setUpAjaxRequestHeaders(): void
    {
        $this->old_request_with           = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : null;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
    }

    /**
     * @after
     */
    protected function restoreAjaxRequestHeaders()
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
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns(null);

        $expected = [];
        $GLOBALS['Response']->shouldReceive('sendJSON')->with($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendsParentsRemainingEffortEvenIfTaskDontHaveOne(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns($this->user_story);

        $user_story_id = $this->user_story->getId();
        $expected = [$user_story_id => ['remaining_effort' => 23]];
        $GLOBALS['Response']->shouldReceive('sendJSON')->with($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNotSendParentWhenParentHasNoRemainingEffortField(): void
    {
        $this->setUpAjaxRequestHeaders();
        $tracker_user_story_id = 110;
        $tracker_user_story    = Mockery::spy(Tracker::class);
        $tracker_user_story->shouldReceive('getId')->andReturn($tracker_user_story_id);
        $user_story_id         = 111;
        $user_story            = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $user_story->shouldReceive('getTracker')->andReturns($tracker_user_story);
        $user_story->shouldReceive('getId')->andReturns($user_story_id);
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns($user_story);

        $expected      = [];
        $GLOBALS['Response']->shouldReceive('sendJSON')->with($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendTheAutocomputedValueOfTheArtifact(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->shouldReceive('getComputableFieldByNameForUser')->with($this->tracker_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->andReturns($this->computed_field);
        $tracker        = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($this->tracker_id);
        $task           = Mockery::spy(Artifact::class);
        $task->shouldReceive('getId')->andReturn($this->artifact_id);
        $task->shouldReceive('getTracker')->andReturn($tracker);
        $visit_recorder = \Mockery::spy(\Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder::class);

        $action = new Tracker_Action_UpdateArtifact(
            $task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $visit_recorder,
            $this->hidden_fieldsets_detector
        );

        $this->computed_field->shouldReceive('getName')->andReturns(Tracker::REMAINING_EFFORT_FIELD_NAME);
        $task->shouldReceive('getTracker')->andReturns($tracker);
        $this->computed_field->shouldReceive('fetchCardValue')->with($task)->andReturns(42);
        $tracker->shouldReceive('hasFormElementWithNameAndType')->andReturns(true);
        $this->computed_field->shouldReceive('isArtifactValueAutocomputed')->andReturns(true);

        $expected      = [
            $this->artifact_id => ['remaining_effort' => '42 (autocomputed)']
        ];
        $GLOBALS['Response']->shouldReceive('sendJSON')->with($expected)->once();

        $action->process($this->layout, $this->request, $this->user);
    }

    public function testItSendsTheRemainingEffortOfTheArtifactAndItsParent(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->shouldReceive('getComputableFieldByNameForUser')->with($this->tracker_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->andReturns($this->computed_field);
        $this->computed_field->shouldReceive('isArtifactValueAutocomputed')->andReturns(false);
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns($this->user_story);

        $user_story_id = $this->user_story->getId();
        $expected      = [
            $this->artifact_id => ['remaining_effort' => 42],
            $user_story_id     => ['remaining_effort' => 23]
        ];
        $GLOBALS['Response']->shouldReceive('sendJSON')->with($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testItDoesNotSendParentsRemainingEffortWhenThereIsNoParent(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->shouldReceive('getComputableFieldByNameForUser')->with($this->tracker_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->andReturns($this->computed_field);
        $this->computed_field->shouldReceive('isArtifactValueAutocomputed')->andReturns(false);
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns(null);

        $expected = [
            $this->artifact_id => ['remaining_effort' => 42],
        ];
        $GLOBALS['Response']->shouldReceive('sendJSON')->with($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function testSendCardInfoOnUpdateWithRemainingEffortItDoesNotSendParentWhenParentHasNoRemainingEffortField(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->formelement_factory->shouldReceive('getComputableFieldByNameForUser')->with($this->tracker_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->andReturns($this->computed_field);
        $tracker_user_story_id = 110;
        $tracker_user_story    = Mockery::spy(Tracker::class);
        $tracker_user_story->shouldReceive('getId')->andReturn($tracker_user_story_id);
        $user_story_id         = 111;
        $user_story            = \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);

        $this->computed_field->shouldReceive('isArtifactValueAutocomputed')->andReturns(false);
        $user_story->shouldReceive('getTracker')->andReturns($tracker_user_story);
        $user_story->shouldReceive('getId')->andReturns($user_story_id);
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns($user_story);

        $expected = [
            $this->artifact_id => ['remaining_effort' => 42],
        ];
        $GLOBALS['Response']->shouldReceive('sendJSON')->with($expected)->once();

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
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns(null);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], Mockery::spy(ProjectManager::class));

        $this->task->shouldReceive('createNewChangeset')->once()->andReturns(true);

        $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
    }

    public function testItReturnsTheScriptTagIfRequestIsFromOverlay(): void
    {
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns($this->user_story);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], Mockery::spy(ProjectManager::class));

        $from_overlay = $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
        $expected     = '<script>window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition(' . $this->task->getId() . ')</script>';
        $this->assertSame($expected, $from_overlay);
    }

    public function testItDoesntReturnScriptWhenInAjax(): void
    {
        $this->setUpAjaxRequestHeaders();
        $this->hierarchy_factory->shouldReceive('getParentArtifact')->with($this->user, $this->task)->andReturns(null);
        $request = new Codendi_Request(['func' => 'artifact-update', 'from_overlay' => '1'], Mockery::spy(ProjectManager::class));

        $from_overlay = $this->getProcessAndCaptureOutput($this->layout, $request, $this->user);
        $this->assertStringNotContainsStringIgnoringCase('<script>', $from_overlay);
    }

    private function getRedirectUrlFor(array $request_data)
    {
        $request        = new Codendi_Request($request_data, Mockery::spy(ProjectManager::class));
        $visit_recorder = \Mockery::spy(\Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder::class);
        $action = new class (
            $this->task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $visit_recorder,
            $this->hidden_fieldsets_detector
        ) extends Tracker_Action_UpdateArtifact
        {
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
        $this->assertEquals(TRACKER_BASE_URL . "/?tracker=$this->tracker_id", $redirect_uri->toUrl());
    }

    public function testItStaysOnTheCurrentArtifactWhenSubmitAndStayIsSpecified(): void
    {
        $request_data = ['submit_and_stay' => true];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEquals(TRACKER_BASE_URL . "/?aid=$this->artifact_id", $redirect_uri->toUrl());
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
        $request_data = ['from_aid' => $from_aid,
                              'submit_and_stay' => true];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $this->artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "from_aid", $from_aid);
    }

    public function testSubmitAndStayHasPrecedenceOverReturnToAid(): void
    {
        $request_data = ['submit_and_stay' => true];
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $this->artifact_id);
    }
}
