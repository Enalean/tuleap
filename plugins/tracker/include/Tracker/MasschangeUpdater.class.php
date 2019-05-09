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

class Tracker_MasschangeUpdater {

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Report */
    private $tracker_report;


    public function __construct(Tracker $tracker, Tracker_Report $tracker_report) {
        $this->tracker        = $tracker;
        $this->tracker_report = $tracker_report;
    }

    public function updateArtifacts(PFUser $user, Codendi_Request $request) {
        if ($this->tracker->userIsAdmin($user)) {

            $masschange_aids = $request->get('masschange_aids');
            if (empty($masschange_aids)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_masschange_detail', 'no_items_selected'));
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->tracker->getId());
            }

            $unsubscribe = $request->get('masschange-unsubscribe-option');
            if ($unsubscribe) {
                $this->unsubscribeUserFromEachArtifactNotification($user, $request, $masschange_aids);
            }

            $send_notifications = $this->getSendNotificationsFromRequest($request);
            $masschange_data    = $request->get('artifact');

            if (! $unsubscribe && empty($masschange_data) ) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_masschange_detail', 'no_items_selected'));
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->tracker->getId());
            }

            $comment = $request->get('artifact_masschange_followup_comment');

            $masschange_values_extractor = new Tracker_MasschangeDataValueExtractor();
            $new_fields_data             = $masschange_values_extractor->getNewValues($masschange_data);

            if (count($new_fields_data) > 0 || $comment !== '') {
                $comment_format = $request->get('comment_formatmass_change');
                $this->tracker->updateArtifactsMasschange(
                    $user,
                    $masschange_aids,
                    $new_fields_data,
                    $comment,
                    $send_notifications,
                    $comment_format
                );
            }

            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->tracker->getId());

        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->tracker_report->getId());
        }
    }

    private function unsubscribeUserFromEachArtifactNotification(PFUser $user, Codendi_Request $request, array $masschange_aids) {
        foreach ($masschange_aids as $artifact_id) {
            $notification_subscriber = $this->getArtifactNotificationSubscriber($artifact_id);
            $notification_subscriber->unsubscribeUserWithoutRedirect($user, $request);
        }

        $GLOBALS['Response']->addFeedback(
            'info',
            $GLOBALS['Language']->getText(
                'plugin_tracker_masschange_detail',
                'unsubscribe_aids',
                implode(', ', $masschange_aids)
            )
        );
    }

    /**
     * @return Tracker_ArtifactNotificationSubscriber
     */
    protected function getArtifactNotificationSubscriber($artifact_id) {
        return new Tracker_ArtifactNotificationSubscriber(
            Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id),
            new Tracker_ArtifactDao()
        );
    }

    /**
     * @return bool
     */
    private function getSendNotificationsFromRequest(Codendi_Request $request) {
        $send_notifications = false;
        if ($request->exist('notify')) {
            if ($request->get('notify') == 'ok') {
                $send_notifications = true;
            }
        }

        return $send_notifications;
    }

}