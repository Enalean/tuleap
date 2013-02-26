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
require_once('bootstrap.php');

class Tracker_Artifact_RedirectUrlTestVersion extends Tracker_Artifact {
    public function getRedirectUrlAfterArtifactUpdate(Codendi_Request $request) {
        return parent::getRedirectUrlAfterArtifactUpdate($request);
    }
}

class Tracker_Artifact_RedirectUrlTest extends TuleapTestCase {
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

    public function itReturnsToThePreviousArtifactWhen_fromAid_isGiven() {
        $from_aid     = 33;
        $request_data = array('from_aid' => $from_aid);
        $artifact_id  = 66;
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertEqual(TRACKER_BASE_URL."/?aid=$from_aid", $redirect_uri->toUrl());
    }

    public function testSubmitAndStayHasPrecedenceOver_fromAid() {
        $from_aid     = 33;
        $artifact_id  = 66;
        $request_data = array('from_aid' => $from_aid,
            'submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "from_aid", $from_aid);
    }

    public function testSubmitAndStayHasPrecedenceOver_returnToAid() {
        $artifact_id  = 66;
        $request_data = array('submit_and_stay' => true);
        $redirect_uri = $this->getRedirectUrlFor($request_data, null, $artifact_id);
        $this->assertUriHasArgument($redirect_uri->toUrl(), "aid", $artifact_id);
    }

    private function getRedirectUrlFor($request_data, $tracker_id, $artifact_id) {
        $request  = new Codendi_Request($request_data);
        $artifact = new Tracker_Artifact_RedirectUrlTestVersion($artifact_id, $tracker_id, 0, null, null, false);
        return $artifact->getRedirectUrlAfterArtifactUpdate($request);

    }

}

?>