<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ChangesetValue\AddDefaultValuesToFieldsData;
use Tuleap\Tracker\Artifact\Link\HandleUpdateArtifact;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\BuildFieldDataFromValuesByFieldStub;
use Tuleap\Tracker\Test\Stub\BuildFieldsDataStub;
use Tuleap\Tracker\Test\Stub\HandleUpdateArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

final class ArtifactCreatorTest extends TestCase
{
    private ArtifactCreator $creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Tracker_ArtifactFactory|Tracker_ArtifactFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TrackerFactory|TrackerFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $tracker_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AddDefaultValuesToFieldsData|AddDefaultValuesToFieldsData&\PHPUnit\Framework\MockObject\MockObject
     */
    private $default_values_adder;
    private HandleUpdateArtifact $artifact_update_handler;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withProject(new \Project(["group_id" => 101, "group_name" => "my-group", "icon_codepoint" => ""]))
            ->build();

        $fields_data_builder           = BuildFieldsDataStub::buildWithDefaultsForInitialChangeset();
        $this->artifact_factory        = $this->createMock(Tracker_ArtifactFactory::class);
        $this->tracker_factory         = RetrieveTrackerStub::withTracker($this->tracker);
        $values_by_field_builder       = BuildFieldDataFromValuesByFieldStub::buildWithDefaults();
        $this->default_values_adder    = $this->createMock(AddDefaultValuesToFieldsData::class);
        $this->artifact_update_handler = HandleUpdateArtifactStub::build();
        $submit_permission_verifier    = VerifySubmissionPermissionStub::withSubmitPermission();

        $this->creator = new ArtifactCreator(
            $fields_data_builder,
            $this->artifact_factory,
            $this->tracker_factory,
            $values_by_field_builder,
            $this->default_values_adder,
            $this->artifact_update_handler,
            $submit_permission_verifier
        );
    }

    public function testItCreatesArtifactAndAddReverseLinks(): void
    {
        $user              = UserTestBuilder::buildWithDefaults();
        $tracker_reference = TrackerReference::build($this->tracker);
        $values            = [];

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->default_values_adder->method("getUsedFieldsWithDefaultValue")->willReturn([]);
        $this->artifact_factory->method('createArtifact')->willReturn($artifact);

        $this->creator->create($user, $tracker_reference, $values, true);
        $this->assertSame(1, $this->artifact_update_handler->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
    }

    public function testItCreateArtifactWithValuesByFieldNameAndAddReverseLinks(): void
    {
        $user              = UserTestBuilder::buildWithDefaults();
        $tracker_reference = TrackerReference::build($this->tracker);
        $values            = [];

        $artifact = ArtifactTestBuilder::anArtifact(1)->inTracker($this->tracker)->build();
        $this->default_values_adder->method("getUsedFieldsWithDefaultValue")->willReturn([]);
        $this->artifact_factory->method('createArtifact')->willReturn($artifact);

        $this->creator->createWithValuesIndexedByFieldName($user, $tracker_reference, $values);
        $this->assertSame(1, $this->artifact_update_handler->getLinkAndUpdateTypeOfReverseArtifactMethodCallCount());
    }
}
