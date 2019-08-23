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

require_once('bootstrap.php');

class ArtifactNotificationSubscriberTest extends TuleapTestCase {

    /** @var Tracker_ArtifactNotificationSubscriber */
    private $artifact_subscriber;

    /** @var Codendi_Request */
    private $request;

    /** @var PFUser */
    private $user;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Tracker_ArtifactDao */
    private $artifact_dao;

    public function setUp()
    {
        parent::setUp();

        $this->artifact     = stub('Tracker_Artifact')->getId()->returns(201);
        $this->artifact_dao = mock('Tracker_ArtifactDao');

        $this->user    = stub('PFUser')->getId()->returns(101);
        $this->request = mock('Codendi_Request');

        $this->artifact_subscriber = new Tracker_ArtifactNotificationSubscriber(
            $this->artifact,
            $this->artifact_dao
        );
    }

    public function itsubscribeUser()
    {
        stub($this->artifact)->userCanView($this->user)->returns(true);

        expect($this->artifact_dao)->deleteUnsubscribeNotification(201, 101)->once();

        $this->artifact_subscriber->subscribeUser($this->user, $this->request);
    }

    public function itUnsubscribeUser()
    {
        stub($this->artifact)->userCanView($this->user)->returns(true);

        expect($this->artifact_dao)->createUnsubscribeNotification(201, 101)->once();

        $this->artifact_subscriber->unsubscribeUser($this->user, $this->request);
    }

}