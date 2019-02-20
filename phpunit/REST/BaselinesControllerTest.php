<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Baseline\REST;

require_once __DIR__ . "/../bootstrap.php";

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetValue_String;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Text;
use Tuleap\Baseline\ArtifactPermissions;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager;

class BaselinesControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    private const ARTIFACT_ID = 23;

    private const ARTIFACT_TITLE = 'Show artifact semantic fields';

    private const ARTIFACT_DESCRIPTION = 'Show only string and numerical field types';

    private const ARTIFACT_STATUS = 'On going';

    private const CHANGESET_TIMESTAMP = 1555459100;

    private const INPUT_DATE = "2019-04-17";

    private const INPUT_DATE_TO_TIMESTAMP_AT_MIDNIGHT = 1555459200;

    private $artifact;

    private $changeset;

    /**
     * @var BaselinesController
     */
    private $controller;

    /**
     * @var UserManager|MockInterface
     */
    private $user_manager;

    /**
     * @var Tracker_Artifact_ChangesetFactory|MockInterface
     */
    private $changeset_factory;

    /**
     * @var Tracker_ArtifactFactory|MockInterface
     */
    private $artifact_factory;

    /**
     * @var FieldRepository|MockInterface
     */
    private $tracker_repository;

    /**
     * @var ArtifactPermissions|MockInterface
     */
    private $artifact_permissions;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->user_manager         = Mockery::mock(UserManager::class);
        $this->changeset_factory    = Mockery::mock(Tracker_Artifact_ChangesetFactory::class);
        $this->artifact_factory     = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->tracker_repository   = Mockery::mock(FieldRepository::class);
        $this->artifact_permissions = Mockery::mock(ArtifactPermissions::class);

        $this->controller = new BaselinesController(
            $this->user_manager,
            $this->changeset_factory,
            $this->artifact_factory,
            $this->tracker_repository,
            $this->artifact_permissions
        );

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn(new PFUser(['timezone' => 'GMT']))
            ->byDefault();

        $tracker        = Mockery::mock(Tracker::class);
        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->artifact->shouldReceive('getTracker')
            ->andReturn($tracker)
            ->byDefault();
        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with(self::ARTIFACT_ID)
            ->andReturn($this->artifact)
            ->byDefault();

        $this->artifact_permissions->shouldReceive('checkRead')
            ->with($this->artifact)
            ->byDefault();

        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->changeset_factory
            ->shouldReceive('getChangesetAtTimestamp')
            ->with($this->artifact, self::INPUT_DATE_TO_TIMESTAMP_AT_MIDNIGHT)
            ->andReturn($this->changeset)
            ->byDefault();

        $title_field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $title_value = Mockery::mock(Tracker_Artifact_ChangesetValue_String::class)
            ->shouldReceive('getValue')
            ->andReturn(self::ARTIFACT_TITLE)
            ->getMock();
        $this->changeset->shouldReceive('getValue')
            ->with($title_field)
            ->andReturn($title_value)
            ->byDefault();

        $description_field = Mockery::mock(Tracker_FormElement_Field_Text::class);
        $description_value = Mockery::mock(Tracker_Artifact_ChangesetValue_String::class)
            ->shouldReceive('getValue')
            ->andReturn(self::ARTIFACT_DESCRIPTION)
            ->getMock();
        $this->changeset->shouldReceive('getValue')
            ->with($description_field)
            ->andReturn($description_value)
            ->byDefault();

        $status_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getFirstValueFor')
            ->with($this->changeset)
            ->andReturn(self::ARTIFACT_STATUS)
            ->byDefault();

        $this->changeset->shouldReceive('getSubmittedOn')
            ->andReturn(self::CHANGESET_TIMESTAMP)
            ->byDefault();

        $this->tracker_repository
            ->shouldReceive('findTitleByTracker')
            ->with($tracker)
            ->andReturn($title_field)
            ->byDefault();
        $this->tracker_repository
            ->shouldReceive('findDescriptionByTracker')
            ->with($tracker)
            ->andReturn($description_field)
            ->byDefault();
        $this->tracker_repository
            ->shouldReceive('findStatusByTracker')
            ->with($tracker)
            ->andReturn($status_field)
            ->byDefault();
    }

    public function testGetByArtifactIdAndDate()
    {
        $representation = $this->controller->getByArtifactIdAndDate(
            self::ARTIFACT_ID,
            self::INPUT_DATE
        );

        $this->assertEquals(self::ARTIFACT_TITLE, $representation->artifact_title);
        $this->assertEquals(self::ARTIFACT_DESCRIPTION, $representation->artifact_description);
        $this->assertEquals(self::ARTIFACT_STATUS, $representation->artifact_status);
        $this->assertEquals(self::CHANGESET_TIMESTAMP, $representation->last_modification_date_before_baseline_date);
    }

    public function testGetByArtifactIdAndDateThrowWhenUserCanNotViewArtifact()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);

        $this->artifact_permissions->shouldReceive('checkRead')
            ->andThrow(new NotAuthorizedException('not authorized'));

        $this->controller->getByArtifactIdAndDate(self::ARTIFACT_ID, self::INPUT_DATE);
    }

    public function testGetByArtifactIdAndDateThrowsWhenGivenDateFormatIsInvalid()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->controller->getByArtifactIdAndDate(self::ARTIFACT_ID, "not a date");
    }

    public function testGetByArtifactIdAndDateThrowsWhenNotArtifactExistWithGivenId()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->artifact_factory
            ->shouldReceive('getArtifactById')
            ->with(self::ARTIFACT_ID)
            ->andReturn(null);

        $this->controller->getByArtifactIdAndDate(self::ARTIFACT_ID, self::INPUT_DATE);
    }

    public function testGetByArtifactIdAndDateReturnsEmptyWhenNoChangeset()
    {
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(404);

        $this->changeset_factory
            ->shouldReceive('getChangesetAtTimestamp')
            ->andReturn(null);

        $this->controller->getByArtifactIdAndDate(self::ARTIFACT_ID, self::INPUT_DATE);
    }

    public function testGetByArtifactIdAndDateReturnsNullTitleWhenNoFieldWithTitleSemantic()
    {
        $this->tracker_repository
            ->shouldReceive('findTitleByTracker')
            ->andReturn(null);

        $representation = $this->controller->getByArtifactIdAndDate(
            self::ARTIFACT_ID,
            self::INPUT_DATE
        );

        $this->assertNull($representation->artifact_title);
    }
}
