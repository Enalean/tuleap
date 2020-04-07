<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

class ArtifactNotificationSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\GlobalResponseMock;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifact     = Mockery::mock(\Tracker_Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(201);
        $this->artifact->shouldReceive('getUri');
        $this->artifact_dao = \Mockery::spy(\Tracker_ArtifactDao::class);

        $this->user    = Mockery::mock(\PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(101);
        $this->request = \Mockery::spy(\Codendi_Request::class);

        $this->artifact_subscriber = new Tracker_ArtifactNotificationSubscriber(
            $this->artifact,
            $this->artifact_dao
        );
    }

    public function testItSubscribeUser(): void
    {
        $this->artifact->shouldReceive('userCanView')->withArgs([$this->user])->andReturn(true);

        $this->artifact_dao->shouldReceive('deleteUnsubscribeNotification')->withArgs([201, 101])->once();

        $this->artifact_subscriber->subscribeUser($this->user, $this->request);
    }

    public function testItUnsubscribeUser(): void
    {
        $this->artifact->shouldReceive('userCanView')->withArgs([$this->user])->andReturn(true);

        $this->artifact_dao->shouldReceive('createUnsubscribeNotification')->withArgs([201, 101])->once();

        $this->artifact_subscriber->unsubscribeUser($this->user, $this->request);
    }
}
