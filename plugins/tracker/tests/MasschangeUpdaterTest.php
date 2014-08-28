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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_MasschangeUpdaterTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $GLOBALS['Response'] = mock('Layout');
    }

    public function tearDown() {
        $GLOBALS['Response'] = null;

        parent::tearDown();
    }

    public function itUpdatesArtifactsWithoutBeenUnsubscribedFromNotifications() {
        $tracker        = mock('Tracker');
        $tracker_report = mock('Tracker_Report');
        $user           = mock('PFUser');
        $request        = mock('Codendi_Request');

        stub($tracker)->userIsAdmin($user)->returns(true);

        stub($request)->get('masschange_aids')->returns(array(201,202));
        stub($request)->get('masschange-unsubscribe-option')->returns(false);
        stub($request)->get('artifact')->returns(array(
            1 => 'Value01'
        ));
        stub($request)->get('artifact_masschange_followup_comment')->returns('');

        $masschange_updater = new Tracker_MasschangeUpdater($tracker, $tracker_report);

        expect($tracker)->updateArtifactsMasschange()->once();

        $masschange_updater->updateArtifacts($user, $request);
    }

    public function itUpdatesArtifactsAndUserHasBeenUnsubscribedFromNotifications() {
        $tracker        = mock('Tracker');
        $tracker_report = mock('Tracker_Report');
        $user           = mock('PFUser');
        $request        = mock('Codendi_Request');

        stub($tracker)->userIsAdmin($user)->returns(true);

        stub($request)->get('masschange_aids')->returns(array(201,202));
        stub($request)->get('masschange-unsubscribe-option')->returns(true);
        stub($request)->get('artifact')->returns(array(
            1 => 'Value01'
        ));
        stub($request)->get('artifact_masschange_followup_comment')->returns('');

        $masschange_updater = partial_mock(
            'Tracker_MasschangeUpdater',
            array('getArtifactNotificationSubscriber'),
            array($tracker, $tracker_report)
        );

        $notification_subscriber = mock('Tracker_ArtifactNotificationSubscriber');
        stub($masschange_updater)->getArtifactNotificationSubscriber()->returns($notification_subscriber);

        $notification_subscriber->expectCallCount('unsubscribeUserWithoutRedirect', 2);
        expect($tracker)->updateArtifactsMasschange()->once();

        $masschange_updater->updateArtifacts($user, $request);
    }
}