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

require_once __DIR__ . '/../../bootstrap.php';

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

class Tracker_Artifact_Update_BaseTest extends TuleapTestCase
{

    /** @var Tracker_Artifact */
    protected $task;

    /** @var Tracker_Artifact */
    protected $user_story;

    /** @var int */
    protected $artifact_id = 123;

    /** @var int */
    protected $tracker_id = 101;

    /** @var Tracker_FormElement_Field_Computed */
    protected $computed_field;

    /** @var Tracker_FormElement_Field_Computed */
    protected $us_computed_field;

    protected $old_request_with;

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

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $tracker_user_story_id     = 103;
        $user_story_id             = 107;
        $submitted_by              = 102;
        $submitted_on              = 1234567890;
        $use_artifact_permissions  = false;
        $tracker                   = aMockeryTracker()->withId($this->tracker_id)->build();
        $this->layout              = \Mockery::spy(\Tracker_IDisplayTrackerLayout::class);
        $this->request             = aRequest()->with('func', 'artifact-update')->build();
        $this->user                = \Mockery::spy(\PFUser::class);
        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->hierarchy_factory   = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $this->computed_field      = \Mockery::spy(\Tracker_FormElement_Field_Computed::class);
        $this->us_computed_field   = \Mockery::spy(\Tracker_FormElement_Field_Computed::class);
        $this->user_story          = \Mockery::spy(\Tracker_Artifact::class);
        $tracker_user_story        = aMockeryTracker()->withId($tracker_user_story_id)->build();

        stub($this->user_story)->getTrackerId()->returns($tracker_user_story_id);
        stub($this->user_story)->getTracker()->returns($tracker_user_story);
        stub($this->user_story)->getId()->returns($user_story_id);

        $this->task = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->task->setHierarchyFactory($this->hierarchy_factory);
        $this->task->setTracker($tracker);
        $this->task->setFormElementFactory($this->formelement_factory);
        $this->task->setId($this->artifact_id);
        $this->task->shouldReceive('createNewChangeset')->andReturns(true)->byDefault();
        stub($this->formelement_factory)->getComputableFieldByNameForUser($tracker_user_story_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->returns($this->us_computed_field);

        stub($this->computed_field)->fetchCardValue($this->task)->returns(42);
        stub($this->us_computed_field)->fetchCardValue($this->user_story)->returns(23);

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

    protected function setUpAjaxRequestHeaders()
    {
        $this->old_request_with           = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : null;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
    }

    protected function restoreAjaxRequestHeaders()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->old_request_with;
    }
}

class Tracker_Artifact_SendCardInfoOnUpdate_WithoutRemainingEffortTest extends Tracker_Artifact_Update_BaseTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->computed_field)->isArtifactValueAutocomputed()->returns(false);
        $this->setUpAjaxRequestHeaders();
    }

    public function tearDown()
    {
        $this->restoreAjaxRequestHeaders();
        parent::tearDown();
    }

    public function itDoesNotSendAnythingIfNoRemainingEffortFieldIsDefinedOnTask()
    {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns(null);

        $expected = array();
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itSendsParentsRemainingEffortEvenIfTaskDontHaveOne()
    {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns($this->user_story);

        $user_story_id = $this->user_story->getId();
        $expected = array($user_story_id => array('remaining_effort' => 23));
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentWhenParentHasNoRemainingEffortField()
    {
        $tracker_user_story_id = 110;
        $tracker_user_story    = aMockeryTracker()->withId($tracker_user_story_id)->build();
        $user_story_id         = 111;
        $user_story            = \Mockery::spy(\Tracker_Artifact::class);

        stub($user_story)->getTracker()->returns($tracker_user_story);
        stub($user_story)->getId()->returns($user_story_id);
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns($user_story);

        $expected      = array();
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }
}

class Tracker_Artifact_SendCardInfoOnUpdate_WithRemainingEffortTest extends Tracker_Artifact_Update_BaseTest
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpAjaxRequestHeaders();
        stub($this->formelement_factory)->getComputableFieldByNameForUser(
            $this->tracker_id,
            Tracker::REMAINING_EFFORT_FIELD_NAME,
            $this->user
        )->returns($this->computed_field);
    }

    public function tearDown()
    {
        $this->restoreAjaxRequestHeaders();
        parent::tearDown();
    }

    public function itSendTheAutocomputedValueOfTheArtifact()
    {
        $tracker        = aMockeryTracker()->withId($this->tracker_id)->build();
        $task           = aMockArtifact()->withId($this->artifact_id)->withTracker($tracker)->build();
        $visit_recorder = \Mockery::spy(\Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder::class);

        $action = new Tracker_Action_UpdateArtifact(
            $task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $visit_recorder,
            $this->hidden_fieldsets_detector
        );

        stub($GLOBALS['Language'])->getText('plugin_tracker', 'autocomputed_field')->returns('autocomputed');
        stub($this->computed_field)->getName()->returns(Tracker::REMAINING_EFFORT_FIELD_NAME);
        stub($task)->getTracker()->returns($tracker);
        stub($this->computed_field)->fetchCardValue($task)->returns(42);
        stub($tracker)->hasFormElementWithNameAndType()->returns(true);
        stub($this->computed_field)->isArtifactValueAutocomputed()->returns(true);

        $expected      = array(
            $this->artifact_id => array('remaining_effort' => '42 (autocomputed)')
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $action->process($this->layout, $this->request, $this->user);
    }

    public function itSendsTheRemainingEffortOfTheArtifactAndItsParent()
    {
        stub($this->computed_field)->isArtifactValueAutocomputed()->returns(false);
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns($this->user_story);

        $user_story_id = $this->user_story->getId();
        $expected      = array(
            $this->artifact_id => array('remaining_effort' => 42),
            $user_story_id     => array('remaining_effort' => 23)
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentsRemainingEffortWhenThereIsNoParent()
    {
        stub($this->computed_field)->isArtifactValueAutocomputed()->returns(false);
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns(null);

        $expected = array(
            $this->artifact_id => array('remaining_effort' => 42),
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentWhenParentHasNoRemainingEffortField()
    {
        $tracker_user_story_id = 110;
        $tracker_user_story    = aMockeryTracker()->withId($tracker_user_story_id)->build();
        $user_story_id         = 111;
        $user_story            = \Mockery::spy(\Tracker_Artifact::class);

        stub($this->computed_field)->isArtifactValueAutocomputed()->returns(false);
        stub($user_story)->getTracker()->returns($tracker_user_story);
        stub($user_story)->getId()->returns($user_story_id);
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns($user_story);

        $expected = array(
            $this->artifact_id => array('remaining_effort' => 42),
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }
}

class Tracker_Artifact_UpdateActionFromOverlay extends Tracker_Artifact_Update_BaseTest
{

    public function itCreatesAChangeset()
    {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns(null);
        $request      = aRequest()->with('func', 'artifact-update')->with('from_overlay', '1')->build();

        expect($this->task)->createNewChangeset()->once()->returns(true);

        $this->getProccesAndCaptureOutput($this->layout, $request, $this->user);
    }

    public function itReturnsTheScriptBaliseIfRequestIsFromOverlay()
    {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns($this->user_story);
        $request      = aRequest()->with('func', 'artifact-update')->with('from_overlay', '1')->build();

        $from_overlay = $this->getProccesAndCaptureOutput($this->layout, $request, $this->user);
        $expected     = '<script>window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition(' . $this->task->getId() . ')</script>';
        $this->assertIdentical($from_overlay, $expected);
    }

    public function itDoesntReturnScriptWhenInAjax()
    {
        $this->setUpAjaxRequestHeaders();
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->task)->returns(null);
        $request      = aRequest()->with('func', 'artifact-update')->with('from_overlay', '1')->build();

        $from_overlay = $this->getProccesAndCaptureOutput($this->layout, $request, $this->user);
        $this->assertNoPattern('/<script>/i', $from_overlay);
        $this->restoreAjaxRequestHeaders();
    }

    private function getProccesAndCaptureOutput($layout, $request, $user)
    {
        ob_start();
        $this->action->process($layout, $request, $user);
        return ob_get_clean();
    }
}

class Tracker_Artifact_RedirectUrlTestVersion extends Tracker_Action_UpdateArtifact
{
    public function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request)
    {
        return parent::getRedirectUrlAfterArtifactUpdate($request);
    }
}

class Tracker_Artifact_RedirectUrlTest extends Tracker_Artifact_Update_BaseTest
{
    public function itRedirectsToTheTrackerHomePageByDefault()
    {
        $request_data = array();
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEqual(TRACKER_BASE_URL . "/?tracker=$this->tracker_id", $redirect_uri->toUrl());
    }

    public function itStaysOnTheCurrentArtifactWhen_submitAndStay_isSpecified()
    {
        $request_data = array('submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEqual(TRACKER_BASE_URL . "/?aid=$this->artifact_id", $redirect_uri->toUrl());
    }

    public function itReturnsToThePreviousArtifactWhen_fromAid_isGiven()
    {
        $from_aid     = 33;
        $request_data = array('from_aid' => $from_aid);
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEqual(TRACKER_BASE_URL . "/?aid=$from_aid", $redirect_uri->toUrl());
    }

    public function testSubmitAndStayHasPrecedenceOver_fromAid()
    {
        $from_aid     = 33;
        $request_data = array('from_aid' => $from_aid,
            'submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $this->artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "from_aid", $from_aid);
    }

    public function testSubmitAndStayHasPrecedenceOver_returnToAid()
    {
        $request_data = array('submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $this->artifact_id);
    }

    private function getRedirectUrlFor($request_data)
    {
        $request        = new Codendi_Request($request_data);
        $visit_recorder = \Mockery::spy(\Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder::class);
        $action         = new Tracker_Artifact_RedirectUrlTestVersion(
            $this->task,
            $this->formelement_factory,
            $this->event_manager,
            $this->artifact_retriever,
            $visit_recorder,
            $this->hidden_fieldsets_detector
        );
        return $action->getRedirectUrlAfterArtifactUpdate($request);
    }
}
