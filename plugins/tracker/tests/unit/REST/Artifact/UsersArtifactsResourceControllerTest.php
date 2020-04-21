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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tracker_Semantic_Title;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\Artifact\MyArtifactsCollection;
use Tuleap\Tracker\TrackerColor;

class UsersArtifactsResourceControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var M\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Tuleap\Tracker\REST\Artifact\UsersArtifactsResourceController
     */
    private $controller;
    /**
     * @var M\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PFUser
     */
    private $current_user;
    /**
     * @var string
     */
    private $current_user_id;

    protected function setUp(): void
    {
        $this->current_user_id  = '101';
        $this->current_user     = M::mock(\PFUser::class, ['getId' => $this->current_user_id]);
        $this->user_manager     = M::mock(\UserManager::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($this->current_user)->byDefault();
        $this->artifact_factory = M::mock(Tracker_ArtifactFactory::class);
        $this->controller = new \Tuleap\Tracker\REST\Artifact\UsersArtifactsResourceController($this->user_manager, $this->artifact_factory);
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
        $this->artifact_factory->shouldReceive('getUserOpenArtifactsSubmittedBy')->with($this->current_user, 0, 250)->once()->andReturn(new MyArtifactsCollection(M::mock(\TrackerFactory::class)));

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"submitted_by": true}', 0, 250);
        $this->assertEquals(0, $total);
        $this->assertEmpty($artifacts);
    }

    public function testFetchArtifactsAssignedToUserWithoutData(): void
    {
        $this->artifact_factory->shouldReceive('getUserOpenArtifactsAssignedTo')->with($this->current_user, 0, 250)->once()->andReturn(new MyArtifactsCollection(M::mock(\TrackerFactory::class)));

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"assigned_to": true}', 0, 250);
        $this->assertEquals(0, $total);
        $this->assertEmpty($artifacts);
    }

    public function testFetchArtifactsAssignedToOrSubmittedByUserWithoutData(): void
    {
        $this->artifact_factory->shouldReceive('getUserOpenArtifactsSubmittedByOrAssignedTo')->with($this->current_user, 0, 250)->once()->andReturn(new MyArtifactsCollection(M::mock(\TrackerFactory::class)));

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"assigned_to": true, "submitted_by": true}', 0, 250);
        $this->assertEquals(0, $total);
        $this->assertEmpty($artifacts);
    }

    public function testFetchArtifactsSubmittedByUserWithData(): void
    {
        $tracker_id = 122;
        $project = new \Project(['group_id' => 333, 'group_name' => '']);
        $tracker = new \Tracker($tracker_id, -1, '', '', '', false, '', '', '', '', '', '', '', TrackerColor::default(), '');
        $tracker->setProject($project);
        $tracker_factory = M::mock(\TrackerFactory::class);
        $tracker_factory->shouldReceive('getTrackerById')->once()->andReturn($tracker);
        Tracker_Semantic_Title::setInstance(M::mock(Tracker_Semantic_Title::class, ['getField' => M::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true])]), $tracker);

        $artifact1 = M::mock(\Tracker_Artifact::class, ['getId' => 455, 'getTitle' => '', 'getUri' => '', 'getXRef' => '', 'getTracker' => $tracker, 'userCanView' => true]);
        $artifact2 = M::mock(\Tracker_Artifact::class, ['getId' => 456, 'getTitle' => 'bar', 'getUri' => '', 'getXRef' => '', 'getTracker' => $tracker, 'userCanView' => true]);

        $my_artifacts_collection = new MyArtifactsCollection($tracker_factory);
        $my_artifacts_collection->setTotalNumberOfArtifacts(2);
        $my_artifacts_collection->setTracker($tracker_id, $this->current_user);
        $my_artifacts_collection->addArtifactForTracker($tracker, $artifact1);
        $my_artifacts_collection->addArtifactForTracker($tracker, $artifact2);

        $this->artifact_factory->shouldReceive('getUserOpenArtifactsSubmittedBy')->with($this->current_user, 0, 250)->once()->andReturn($my_artifacts_collection);

        [$total, $artifacts] = $this->controller->getArtifacts('self', '{"submitted_by": true}', 0, 250);
        $this->assertEquals(2, $total);
        $this->assertEquals('', $artifacts[0]->title);
        $this->assertEquals('bar', $artifacts[1]->title);
    }
}
