<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\MyArtifactsCollection;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UsersArtifactsResourceControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private UsersArtifactsResourceController $controller;
    private UserManager&MockObject $user_manager;
    private \PFUser $current_user;
    private int $current_user_id = 101;

    protected function setUp(): void
    {
        $this->current_user = UserTestBuilder::aUser()->withId($this->current_user_id)->build();
        $this->user_manager = $this->createMock(\UserManager::class);
        $this->user_manager->method('getCurrentUser')->willReturn($this->current_user);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
        $this->controller       = new \Tuleap\Tracker\REST\Artifact\UsersArtifactsResourceController($this->user_manager, $this->artifact_factory);

        UserManager::setInstance($this->user_manager);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
    }

    public function testThrowsForbiddenWhenParameterIsNotSelf(): void
    {
        $this->expectException(RestException::class);
        $this->controller->getArtifacts('123', '');
    }

    public function testThrowsErrorWhenAssignedToQueryIsInvalid(): void
    {
        $this->expectException(RestException::class);
        $this->controller->getArtifacts('self', '{"assigned_to": "me"}', 0, 250);
    }

    public function testThrowsErrorWhenSubmittedByQueryIsInvalid(): void
    {
        $this->expectException(RestException::class);
        $this->controller->getArtifacts('self', '{"submitted_by": "me"}', 0, 250);
    }

    public function testThrowsErrorWhenJsonIsInvalid(): void
    {
        $this->expectException(RestException::class);
        $this->controller->getArtifacts('self', '{"submitted_by":', 0, 250);
    }

    public function testThrowsErrorWhenQueryMakesNoSense(): void
    {
        $this->expectException(RestException::class);
        $this->controller->getArtifacts('self', '{"submitted_by": false}', 0, 250);
    }

    public function testFetchArtifactsSubmittedByUserWithoutData(): void
    {
        $this->artifact_factory->expects($this->once())->method('getUserOpenArtifactsSubmittedBy')->with($this->current_user, 0, 250)
            ->willReturn(new MyArtifactsCollection(RetrieveTrackerStub::withoutTracker(), RetrieveSemanticTitleFieldStub::build()));

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"submitted_by": true}', 0, 250);
        $this->assertEquals(0, $total);
        $this->assertEmpty($artifacts);
    }

    public function testFetchArtifactsAssignedToUserWithoutData(): void
    {
        $this->artifact_factory->expects($this->once())->method('getUserOpenArtifactsAssignedTo')->with($this->current_user, 0, 250)
            ->willReturn(new MyArtifactsCollection(RetrieveTrackerStub::withoutTracker(), RetrieveSemanticTitleFieldStub::build()));

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"assigned_to": true}', 0, 250);
        $this->assertEquals(0, $total);
        $this->assertEmpty($artifacts);
    }

    public function testFetchArtifactsAssignedToOrSubmittedByUserWithoutData(): void
    {
        $this->artifact_factory->expects($this->once())->method('getUserOpenArtifactsSubmittedByOrAssignedTo')->with($this->current_user, 0, 250)
            ->willReturn(new MyArtifactsCollection(RetrieveTrackerStub::withoutTracker(), RetrieveSemanticTitleFieldStub::build()));

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"assigned_to": true, "submitted_by": true}', 0, 250);
        $this->assertEquals(0, $total);
        $this->assertEmpty($artifacts);
    }

    public function testFetchArtifactsSubmittedByUserWithData(): void
    {
        $tracker_id = 122;

        $project = ProjectTestBuilder::aProject()->build();
        $tracker = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject($project)->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory->expects($this->once())->method('getTrackerById')->willReturn($tracker);

        $artifact1 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact1->method('getId')->willReturn(455);
        $artifact1->method('getTitle')->willReturn('');
        $artifact1->method('getUri')->willReturn('');
        $artifact1->method('getXRef')->willReturn('');
        $artifact1->method('getTracker')->willReturn($tracker);
        $artifact1->method('userCanView')->willReturn(true);
        $artifact2 = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $artifact2->method('getId')->willReturn(456);
        $artifact2->method('getTitle')->willReturn('bar');
        $artifact2->method('getUri')->willReturn('');
        $artifact2->method('getXRef')->willReturn('');
        $artifact2->method('getTracker')->willReturn($tracker);
        $artifact2->method('userCanView')->willReturn(true);

        $my_artifacts_collection = new MyArtifactsCollection(
            $tracker_factory,
            RetrieveSemanticTitleFieldStub::build()->withTitleField(
                StringFieldBuilder::aStringField(1001)->withReadPermission($this->current_user, true)->build()
            ),
        );
        $my_artifacts_collection->setTotalNumberOfArtifacts(2);
        $my_artifacts_collection->setTracker($tracker_id, $this->current_user);
        $my_artifacts_collection->addArtifactForTracker($tracker, $artifact1);
        $my_artifacts_collection->addArtifactForTracker($tracker, $artifact2);

        $this->artifact_factory->expects($this->once())
            ->method('getUserOpenArtifactsSubmittedBy')
            ->with($this->current_user, 0, 250)
            ->willReturn($my_artifacts_collection);

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"submitted_by": true}', 0, 250);
        self::assertSame(2, $total);
        self::assertSame('', $artifacts[0]->title);
        self::assertSame('bar', $artifacts[1]->title);
    }
}
