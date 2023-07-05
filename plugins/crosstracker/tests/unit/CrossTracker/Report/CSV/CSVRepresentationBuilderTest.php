<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;

final class CSVRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CSVFormatterVisitor&MockObject $visitor;
    private CSVRepresentationBuilder $builder;
    private \PFUser&MockObject $user;
    private \UserManager&MockObject $user_manager;
    private SimilarFieldCollection&MockObject $similar_fields;
    private SimilarFieldsFormatter&MockObject $similar_fields_formatter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->similar_fields = $this->createMock(SimilarFieldCollection::class);
        $this->user           = $this->createMock(\PFUser::class);
        $this->user->method('getPreference')->with('user_csv_separator')->willReturn(
            CSVRepresentation::COMMA_SEPARATOR_NAME
        );
        $this->visitor                  = $this->createMock(CSVFormatterVisitor::class);
        $this->user_manager             = $this->createMock(\UserManager::class);
        $this->similar_fields_formatter = $this->createMock(SimilarFieldsFormatter::class);
        $this->builder                  = new CSVRepresentationBuilder(
            $this->visitor,
            $this->user_manager,
            $this->similar_fields_formatter
        );
    }

    public function testBuildHeaderLine(): void
    {
        $this->similar_fields->method('getFieldNames')->willReturn(['pentarchical']);

        $result = $this->builder->buildHeaderLine($this->user, $this->similar_fields);

        self::assertEquals(
            'id,project,tracker,title,description,status,submitted_by,submitted_on,last_update_by,last_update_date,pentarchical',
            $result->__toString()
        );
    }

    public function testBuild(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getPublicName')->willReturn('Atacaman');
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getProject')->willReturn($project);
        $tracker->method('getName')->willReturn('freckly');

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->similar_fields->method('getFieldNames')->willReturn([]);

        $artifact_id = 84;

        $artifact->method('getId')->willReturn($artifact_id);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getSubmittedOn')->willReturn(1540456782);
        $artifact->method('getLastUpdateDate')->willReturn(1540478708);
        $artifact->method('getSubmittedBy')->willReturn(992);
        $artifact->method('getLastModifiedBy')->willReturn(851);
        $artifact->method('getTitle')->willReturn('Uncinated unrecantable');
        $artifact->method('getStatus')->willReturn('On going');
        $artifact->method('getDescription')->willReturn(
            'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor
                    incididunt ut labore et dolore magna aliqua.'
        );


        $this->user_manager->method('getUserById')->willReturn($this->createMock(\PFUser::class));

        $formatted_project_name     = '"Atacaman"';
        $formatted_tracker_name     = '"freckly"';
        $formatted_submitted_on     = '25/10/2018 10:39';
        $formatted_last_update_date = '25/10/2018 16:45';
        $formatted_submitted_by     = '"tszwejbka"';
        $formatted_last_update_by   = '"akrostag"';
        $formatted_title            = '"Uncinated unrecantable"';
        $formatted_description      = '"Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor
            incididunt ut labore et dolore magna aliqua."';
        $formatted_status           = '"On going"';

        $this->visitor->method('visitTextValue')
            ->willReturn(
                $formatted_project_name,
                $formatted_tracker_name,
                $formatted_title,
                $formatted_description,
                $formatted_status
            );
        $this->visitor->method('visitDateValue')
            ->willReturn($formatted_submitted_on, $formatted_last_update_date);
        $this->visitor->method('visitUserValue')
            ->willReturn($formatted_submitted_by, $formatted_last_update_by);
        $this->similar_fields_formatter->method('formatSimilarFields')->willReturn([]);

        $expected_representation = new CSVRepresentation();
        $expected_representation->build(
            [
                $artifact_id,
                $formatted_project_name,
                $formatted_tracker_name,
                $formatted_title,
                $formatted_description,
                $formatted_status,
                $formatted_submitted_by,
                $formatted_submitted_on,
                $formatted_last_update_by,
                $formatted_last_update_date,
            ],
            $this->user
        );

        $result = $this->builder->build($artifact, $this->user, $this->similar_fields);

        self::assertEquals($expected_representation, $result);
    }
}
