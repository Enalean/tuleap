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

require_once 'bootstrap.php';

class Tracker_MasschangeUpdaterTest extends TuleapTestCase {

    public function itUpdatesArtifactsWithoutBeenUnsubscribedFromNotifications() {
        $tracker        = \Mockery::spy(\Tracker::class);
        $tracker_report = \Mockery::spy(\Tracker_Report::class);
        $user           = \Mockery::spy(\PFUser::class);
        $request        = \Mockery::spy(\Codendi_Request::class);

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
        $tracker        = \Mockery::spy(\Tracker::class);
        $tracker_report = \Mockery::spy(\Tracker_Report::class);
        $user           = \Mockery::spy(\PFUser::class);
        $request        = \Mockery::spy(\Codendi_Request::class);

        stub($tracker)->userIsAdmin($user)->returns(true);

        stub($request)->get('masschange_aids')->returns(array(201,202));
        stub($request)->get('masschange-unsubscribe-option')->returns(true);
        stub($request)->get('artifact')->returns(array(
            1 => 'Value01'
        ));
        stub($request)->get('artifact_masschange_followup_comment')->returns('');

        $masschange_updater = \Mockery::mock(\Tracker_MasschangeUpdater::class, [$tracker, $tracker_report])->makePartial()->shouldAllowMockingProtectedMethods();

        $notification_subscriber = \Mockery::spy(\Tracker_ArtifactNotificationSubscriber::class);
        stub($masschange_updater)->getArtifactNotificationSubscriber()->returns($notification_subscriber);

        $notification_subscriber->shouldReceive('unsubscribeUserWithoutRedirect')->times(2);
        expect($tracker)->updateArtifactsMasschange()->once();

        $masschange_updater->updateArtifacts($user, $request);
    }
}
