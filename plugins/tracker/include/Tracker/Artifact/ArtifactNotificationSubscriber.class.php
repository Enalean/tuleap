<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\Artifact\Artifact;

class Tracker_ArtifactNotificationSubscriber
{

    /** @var Artifact */
    private $artifact;

    /** @var Tracker_ArtifactDao */
    private $artifact_dao;

    public function __construct(Artifact $artifact, Tracker_ArtifactDao $artifact_dao)
    {
        $this->artifact     = $artifact;
        $this->artifact_dao = $artifact_dao;
    }

    public function unsubscribeUser(PFUser $user, Codendi_Request $request)
    {
        if (! $this->doesUserCanViewArtifact($user, $request)) {
            return;
        }

        $this->unsubscribe($user);
        $this->sendResponse(
            $request,
            'info',
            dgettext('tuleap-tracker', 'You will no-longer receive notifications for this artifact'),
            true
        );

        return;
    }

    public function unsubscribeUserWithoutRedirect(PFUser $user, Codendi_Request $request)
    {
        if (! $this->doesUserCanViewArtifact($user, $request)) {
            return;
        }

        $this->unsubscribe($user);
    }

    public function subscribeUser(PFUser $user, Codendi_Request $request)
    {
        if (! $this->doesUserCanViewArtifact($user, $request)) {
            return;
        }

        $this->subscribe($user);
        $this->sendResponse(
            $request,
            'info',
            dgettext('tuleap-tracker', 'You are now receiving notifications for this artifact'),
            false
        );

        return;
    }

    private function doesUserCanViewArtifact(PFUser $user, Codendi_Request $request)
    {
        if (! $this->artifact->userCanView($user)) {
            $this->sendResponse(
                $request,
                'error',
                dgettext('tuleap-tracker', 'The request is not valid'),
                null
            );
            return false;
        }

        return true;
    }

    private function subscribe(PFUser $user)
    {
        $this->artifact_dao->deleteUnsubscribeNotification($this->artifact->getId(), $user->getId());
    }

    private function unsubscribe(PFUser $user)
    {
        $this->artifact_dao->createUnsubscribeNotification($this->artifact->getId(), $user->getId());
    }

    private function sendResponse(Codendi_Request $request, $feedback_level, $message, $unsubscribe)
    {
        if ($request->isAjax()) {
            $this->sendAjaxResponse($unsubscribe, $message);
            return;
        }

        $GLOBALS['Response']->addFeedback(
            $feedback_level,
            $message
        );
        $GLOBALS['Response']->redirect($this->artifact->getUri());
    }

    private function sendAjaxResponse($unsubscribe, $message)
    {
        $response["notification"] = ! $unsubscribe;
        $response["message"]      = $message;
        $GLOBALS['Response']->sendJSON($response);
    }
}
