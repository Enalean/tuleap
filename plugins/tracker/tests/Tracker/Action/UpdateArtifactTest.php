<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Artifact_Update_BaseTest extends TuleapTestCase {

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

    public function setUp() {
        parent::setUp();

        $tracker_user_story_id     = 103;
        $user_story_id             = 107;
        $submitted_by              = 102;
        $submitted_on              = 1234567890;
        $use_artifact_permissions  = false;
        $tracker                   = aMockTracker()->withId($this->tracker_id)->build();
        $this->layout              = mock('Tracker_IDisplayTrackerLayout');
        $this->request             = aRequest()->with('func', 'artifact-update')->build();
        $this->user                = mock('PFUser');
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->computed_field      = mock('Tracker_FormElement_Field_Computed');
        $this->us_computed_field   = mock('Tracker_FormElement_Field_Computed');
        $this->user_story          = mock('Tracker_Artifact');
        $tracker_user_story        = aMockTracker()->withId($tracker_user_story_id)->build();

        stub($this->user_story)->getTrackerId()->returns($tracker_user_story_id);
        stub($this->user_story)->getTracker()->returns($tracker_user_story);
        stub($this->user_story)->getId()->returns($user_story_id);

        $this->task = partial_mock(
            'Tracker_Artifact',
            array('createNewChangeset'),
            array($this->artifact_id, $this->tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions)
        );
        $this->task->setTracker($tracker);
        $this->task->setFormElementFactory($this->formelement_factory);
        stub($this->task)->createNewChangeset()->returns(true);
        stub($this->formelement_factory)->getComputableFieldByNameForUser($tracker_user_story_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->returns($this->us_computed_field);

        stub($this->computed_field)->fetchCardValue($this->task)->returns(42);
        stub($this->us_computed_field)->fetchCardValue($this->user_story)->returns(23);

        $this->event_manager = mock('EventManager');

        $this->action = new Tracker_Action_UpdateArtifact($this->task, $this->formelement_factory, $this->event_manager);
    }

    protected function setUpAjaxRequestHeaders() {
        $this->old_request_with           = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : null;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHTTPREQUEST';
    }

    protected function restoreAjaxRequestHeaders() {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $this->old_request_with;
    }
}

class Tracker_Artifact_SendCardInfoOnUpdate_WithoutRemainingEffortTest extends Tracker_Artifact_Update_BaseTest {

    public function setUp() {
        parent::setUp();
        $this->setUpAjaxRequestHeaders();
    }

    public function tearDown() {
        $this->restoreAjaxRequestHeaders();
        parent::tearDown();
    }

    public function itDoesNotSendAnythingIfNoRemainingEffortFieldIsDefinedOnTask() {
        $this->task->setAllAncestors(array());

        $expected = array();
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itSendsParentsRemainingEffortEvenIfTaskDontHaveOne() {
        $this->task->setAllAncestors(array($this->user_story));

        $user_story_id = $this->user_story->getId();
        $expected = array($user_story_id => array('remaining_effort' => 23));
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentWhenParentHasNoRemainingEffortField() {
        $tracker_user_story_id = 110;
        $tracker_user_story    = aMockTracker()->withId($tracker_user_story_id)->build();
        $user_story_id         = 111;
        $user_story            = mock('Tracker_Artifact');

        stub($user_story)->getTracker()->returns($tracker_user_story);
        stub($user_story)->getId()->returns($user_story_id);
        $this->task->setAllAncestors(array($user_story));

        $user_story_id = $this->user_story->getId();
        $expected      = array();
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

}

class Tracker_Artifact_SendCardInfoOnUpdate_WithRemainingEffortTest extends Tracker_Artifact_Update_BaseTest {

    public function setUp() {
        parent::setUp();
        $this->setUpAjaxRequestHeaders();
        stub($this->formelement_factory)->getComputableFieldByNameForUser($this->tracker_id, Tracker::REMAINING_EFFORT_FIELD_NAME, $this->user)->returns($this->computed_field);
    }

    public function tearDown() {
        $this->restoreAjaxRequestHeaders();
        parent::tearDown();
    }

    public function itSendsTheRemainingEffortOfTheArtifactAndItsParent() {
        $this->task->setAllAncestors(array($this->user_story));

        $user_story_id = $this->user_story->getId();
        $expected      = array(
            $this->artifact_id => array('remaining_effort' => 42),
            $user_story_id     => array('remaining_effort' => 23)
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentsRemainingEffortWhenThereIsNoParent() {
        $this->task->setAllAncestors(array());

        $expected = array(
            $this->artifact_id => array('remaining_effort' => 42),
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

    public function itDoesNotSendParentWhenParentHasNoRemainingEffortField() {
        $tracker_user_story_id = 110;
        $tracker_user_story    = aMockTracker()->withId($tracker_user_story_id)->build();
        $user_story_id         = 111;
        $user_story            = mock('Tracker_Artifact');

        stub($user_story)->getTracker()->returns($tracker_user_story);
        stub($user_story)->getId()->returns($user_story_id);
        $this->task->setAllAncestors(array($user_story));

        $expected = array(
            $this->artifact_id => array('remaining_effort' => 42),
        );
        expect($GLOBALS['Response'])->sendJSON($expected)->once();

        $this->action->process($this->layout, $this->request, $this->user);
    }

}

class Tracker_Artifact_UpdateActionFromOverlay extends Tracker_Artifact_Update_BaseTest {

    public function itCreatesAChangeset() {
        $this->task->setAllAncestors(array());
        $request      = aRequest()->with('func', 'artifact-update')->with('from_overlay', '1')->build();

        expect($this->task)->createNewChangeset()->once();

        $this->getProccesAndCaptureOutput($this->layout, $request, $this->user);
    }

    public function itReturnsTheScriptBaliseIfRequestIsFromOverlay() {
        $this->task->setAllAncestors(array());
        $request      = aRequest()->with('func', 'artifact-update')->with('from_overlay', '1')->build();

        $from_overlay = $this->getProccesAndCaptureOutput($this->layout, $request, $this->user);
        $expected     = '<script>window.parent.tuleap.cardwall.cardsEditInPlace.validateEdition('.$this->task->getId().')</script>';
        $this->assertIdentical($from_overlay, $expected);
    }

    public function itDoesntReturnScriptWhenInAjax() {
        $this->setUpAjaxRequestHeaders();
        $this->task->setAllAncestors(array());
        $request      = aRequest()->with('func', 'artifact-update')->with('from_overlay', '1')->build();

        $from_overlay = $this->getProccesAndCaptureOutput($this->layout, $request, $this->user);
        $this->assertNoPattern('/<script>/i', $from_overlay);
        $this->restoreAjaxRequestHeaders();
    }

    private function getProccesAndCaptureOutput($layout, $request, $user) {
        ob_start();
        $this->action->process($layout, $request, $user);
        return ob_get_clean();
    }
}

class Tracker_Artifact_RedirectUrlTestVersion extends Tracker_Action_UpdateArtifact {
    public function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request) {
        return parent::getRedirectUrlAfterArtifactUpdate($request);
    }
}

class Tracker_Artifact_RedirectUrlTest extends Tracker_Artifact_Update_BaseTest {
    public function itRedirectsToTheTrackerHomePageByDefault() {
        $request_data = array();
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEqual(TRACKER_BASE_URL."/?tracker=$this->tracker_id", $redirect_uri->toUrl());
    }

    public function itStaysOnTheCurrentArtifactWhen_submitAndStay_isSpecified() {
        $request_data = array('submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEqual(TRACKER_BASE_URL."/?aid=$this->artifact_id", $redirect_uri->toUrl());
    }

    public function itReturnsToThePreviousArtifactWhen_fromAid_isGiven() {
        $from_aid     = 33;
        $request_data = array('from_aid' => $from_aid);
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertEqual(TRACKER_BASE_URL."/?aid=$from_aid", $redirect_uri->toUrl());
    }

    public function testSubmitAndStayHasPrecedenceOver_fromAid() {
        $from_aid     = 33;
        $request_data = array('from_aid' => $from_aid,
            'submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $this->artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "from_aid", $from_aid);
    }

    public function testSubmitAndStayHasPrecedenceOver_returnToAid() {
        $request_data = array('submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $this->artifact_id);
    }

    private function getRedirectUrlFor($request_data) {
        $request  = new Codendi_Request($request_data);
        $action   = new Tracker_Artifact_RedirectUrlTestVersion($this->task, $this->formelement_factory, $this->event_manager);
        return $action->getRedirectUrlAfterArtifactUpdate($request);

    }
}

?>