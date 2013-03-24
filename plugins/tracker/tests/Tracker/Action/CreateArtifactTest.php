<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


class Tracker_Action_CreateArtifact_ProtectedToPublic extends Tracker_Action_CreateArtifact {

    public function redirectToParentCreationIfNeeded(Tracker_Artifact $artifact, PFUser $current_user, Tracker_Artifact_Redirect $redirect) {
        parent::redirectToParentCreationIfNeeded($artifact, $current_user, $redirect);
    }

    public function redirectUrlAfterArtifactSubmission(Codendi_Request $request, $tracker_id, $artifact_id) {
        return parent::redirectUrlAfterArtifactSubmission($request, $tracker_id, $artifact_id);
    }
}

abstract class Tracker_Action_CreateArtifactTest extends TuleapTestCase {
    protected $tracker;
    protected $artifact_factory;
    protected $tracker_factory;
    protected $formelement_factory;
    protected $action;
    protected $event_manager;

    public function setUp() {
        parent::setUp();

        $this->event_manager = mock('EventManager');
        EventManager::setInstance($this->event_manager);
        
        $this->tracker             = mock('Tracker');
        $this->artifact_factory    = mock('Tracker_ArtifactFactory');
        $this->tracker_factory     = mock('TrackerFactory');
        $this->formelement_factory = mock('Tracker_FormElementFactory');

        $this->action = new Tracker_Action_CreateArtifact_ProtectedToPublic(
            $this->tracker,
            $this->artifact_factory,
            $this->tracker_factory,
            $this->formelement_factory
        );
    }

    public function tearDown() {
        parent::tearDown();
        EventManager::clearInstance();
    }
}

class Tracker_Action_CreateArtifact_RedirectUrlTest extends Tracker_Action_CreateArtifactTest {
    public function itRedirectsToTheTrackerHomePageByDefault() {
        $request_data = array();
        $tracker_id   = 20;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, null);
        $this->assertEqual(TRACKER_BASE_URL."/?tracker=$tracker_id", $redirect_uri->toUrl());
    }

    public function itStaysOnTheCurrentArtifactWhen_submitAndStay_isSpecified() {
        $request_data = array('submit_and_stay' => true);
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertEqual(TRACKER_BASE_URL."/?aid=$artifact_id", $redirect_uri->toUrl());
    }

    public function itRedirectsToNewArtifactCreationWhen_submitAndContinue_isSpecified() {
        $request_data = array('submit_and_continue' => true);
        $tracker_id  = 73;
        $artifact_id = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertStringBeginsWith($redirect_uri->toUrl(), TRACKER_BASE_URL);
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'func', 'new-artifact');
        $this->assertUriHasArgument($redirect_uri->toUrl(), 'tracker', $tracker_id);
    }

    public function testSubmitAndContinue() {
        $request_data = array('submit_and_continue' => true);
        $tracker_id   = 73;
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, $tracker_id, $artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "func", 'new-artifact');
    }

    private function getRedirectUrlFor($request_data, $tracker_id, $artifact_id) {
        $request = new Codendi_Request($request_data);
        return $this->action->redirectUrlAfterArtifactSubmission($request, $tracker_id, $artifact_id);
    }
}

class Tracker_Action_CreateArtifact_RedirectToParentCreationTest extends Tracker_Action_CreateArtifactTest {
    private $tracker_id;
    private $current_user;
    private $new_artifact;

    public function setUp() {
        parent::setUp();
        $this->tracker_id   = 999;
        $this->current_user = aUser()->build();
        $this->new_artifact = aMockArtifact()->withId(123)->build();

        $this->hierarchy = mock('Tracker_Hierarchy');

        stub($this->tracker)->getId()->returns($this->tracker_id);

        $this->parent_tracker = aTracker()->withId(666)->build();
        $this->art_link_field = mock('Tracker_FormElement_Field_ArtifactLink');

        $this->redirect = new Tracker_Artifact_Redirect();
    }

    public function itDoesRedirectWhenPackageIsComplete() {
        stub($this->tracker)->getParent()->returns($this->parent_tracker);
        stub($this->formelement_factory)->getAnArtifactLinkField($this->current_user, $this->parent_tracker)->returns($this->art_link_field);

        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect);
        $this->assertNotNull($this->redirect->query_parameters);
    }

    public function itDoesntRedirectWhenNewArtifactAlreadyHasAParent() {
        stub($this->new_artifact)->getAllAncestors()->returns(array(aMockArtifact()->build()));

        stub($this->tracker)->getParent()->returns($this->parent_tracker);
        stub($this->formelement_factory)->getAnArtifactLinkField()->returns($this->art_link_field);

        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect);
        $this->assertNull($this->redirect->query_parameters);
    }

    public function itDoesntRedirectIfThereAreNoHierarchy() {
        $this->action->redirectToParentCreationIfNeeded($this->new_artifact, $this->current_user, $this->redirect);
        $this->assertNull($this->redirect->query_parameters);
    }
}

?>
