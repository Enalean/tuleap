<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;

class CSVRepresentationBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Mockery\MockInterface */
    private $visitor;
    /** @var CSVRepresentationBuilder */
    private $builder;
    /** @var Mockery\MockInterface */
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = Mockery::mock(\PFUser::class);
        $this->user->shouldReceive('getPreference')->withArgs(['user_csv_separator'])->andReturn(
            CSVRepresentation::COMMA_SEPARATOR_NAME
        );
        $this->visitor = Mockery::mock(CSVFormatterVisitor::class);
        $this->builder = new CSVRepresentationBuilder($this->visitor);
    }

    public function testBuildHeaderLine()
    {
        $result = $this->builder->buildHeaderLine($this->user);

        $this->assertEquals('id,project,tracker,submitted_on,last_update_date', $result->__toString());
    }

    public function testBuild()
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getUnconvertedPublicName')->andReturn('Atacaman');
        $tracker = Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $tracker->shouldReceive('getName')->andReturn('freckly');
        $artifact    = Mockery::mock(\Tracker_Artifact::class);
        $artifact_id = 84;
        $artifact->shouldReceive(
            [
                'getId'             => $artifact_id,
                'getTracker'        => $tracker,
                'getSubmittedOn'    => '1540456782',
                'getLastUpdateDate' => '1540478708'
            ]
        );

        $formatted_project_name     = '"Atacaman"';
        $formatted_tracker_name     = '"freckly"';
        $formatted_submitted_on     = '25/10/2018 10:39';
        $formatted_last_update_date = '25/10/2018 16:45';
        $this->visitor->shouldReceive('visitTextValue')
            ->andReturn($formatted_project_name, $formatted_tracker_name);
        $this->visitor->shouldReceive('visitDateValue')
            ->andReturn($formatted_submitted_on, $formatted_last_update_date);

        $expected_representation = new CSVRepresentation();
        $expected_representation->build(
            [
                $artifact_id,
                $formatted_project_name,
                $formatted_tracker_name,
                $formatted_submitted_on,
                $formatted_last_update_date
            ],
            $this->user
        );

        $result = $this->builder->build($artifact, $this->user);

        $this->assertEquals($expected_representation, $result);
    }
}
