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

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactNotificationSubscriberTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Tuleap\GlobalResponseMock;

    private const USER_ID     = 101;
    private const ARTIFACT_ID = 201;

    private Tracker_ArtifactNotificationSubscriber $artifact_subscriber;

    private PFUser $user;

    private Artifact $artifact;

    /** @var Tracker_ArtifactDao */
    private Tracker_ArtifactDao&MockObject $artifact_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserTestBuilder::aUser()->withId(self::USER_ID)->build();

        $this->artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->userCanView($this->user)->build();

        $this->artifact_dao = $this->createMock(\Tracker_ArtifactDao::class);

        $this->artifact_subscriber = new Tracker_ArtifactNotificationSubscriber(
            $this->artifact,
            $this->artifact_dao
        );
    }

    public function testItSubscribeUser(): void
    {
        $this->artifact_dao->expects($this->once())
            ->method('deleteUnsubscribeNotification')
            ->with(self::ARTIFACT_ID, self::USER_ID);

        $this->artifact_subscriber->subscribeUser($this->user, HTTPRequestBuilder::get()->build());
    }

    public function testItUnsubscribeUser(): void
    {
        $this->artifact_dao->expects($this->once())
            ->method('createUnsubscribeNotification')
            ->with(self::ARTIFACT_ID, self::USER_ID);

        $this->artifact_subscriber->unsubscribeUser($this->user, HTTPRequestBuilder::get()->build());
    }
}
