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

namespace Tuleap\TestManagement\XML;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\XML\Importer\ImportedChangesetMapping;

final class XMLImportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItImportsConfigurationFromXMLContent(): void
    {
        $config          = Mockery::mock(Config::class);
        $tracker_checker = Mockery::mock(TrackerChecker::class);
        $execution_dao   = Mockery::mock(ExecutionDao::class);

        $xml_import = new XMLImport($config, $tracker_checker, $execution_dao);

        $artifact_id_mapping = Mockery::spy(Tracker_XML_Importer_ArtifactImportedMapping::class);
        $artifact_id_mapping
            ->shouldReceive('containsSource')
            ->with('123')
            ->once()
            ->andReturn(true);
        $artifact_id_mapping
            ->shouldReceive('get')
            ->with('123')
            ->once()
            ->andReturn(1123);
        $artifact_id_mapping
            ->shouldReceive('containsSource')
            ->with('124')
            ->once()
            ->andReturn(true);
        $artifact_id_mapping
            ->shouldReceive('get')
            ->with('124')
            ->once()
            ->andReturn(1124);

        $changeset_mapping = Mockery::spy(ImportedChangesetMapping::class);
        $changeset_mapping
            ->shouldReceive('get')
            ->with('CHANGESET_10001')
            ->once()
            ->andReturn(11001);
        $changeset_mapping
            ->shouldReceive('get')
            ->with('CHANGESET_10002')
            ->once()
            ->andReturn(11002);

        $project         = Mockery::mock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104
        ];

        $tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->times(2);

        $config->shouldReceive('setProjectConfiguration')
            ->with($project, 102, 103, 104, 101)
            ->once();

        $execution_dao
            ->shouldReceive('updateExecutionToUseSpecificVersionOfDefinition')
            ->with(1123, 104, 11001, 103)
            ->once();
        $execution_dao
            ->shouldReceive('updateExecutionToUseSpecificVersionOfDefinition')
            ->with(1124, 104, 11002, 103)
            ->once();

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $artifact_id_mapping,
            $changeset_mapping
        );
    }

    public function testItImportsNothingIfConfigIsNotSet(): void
    {
        $config          = Mockery::mock(Config::class);
        $tracker_checker = Mockery::mock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, Mockery::mock(ExecutionDao::class));

        $project         = Mockery::mock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [];

        $tracker_checker->shouldReceive('checkTrackerIsInProject')->never();
        $tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->never();
        $config->shouldReceive('setProjectConfiguration')->never();

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            Mockery::spy(Tracker_XML_Importer_ArtifactImportedMapping::class),
            Mockery::spy(ImportedChangesetMapping::class)
        );
    }

    public function testItIDoesNotImportANonProperlySetConfiguration(): void
    {
        $config          = Mockery::mock(Config::class);
        $tracker_checker = Mockery::mock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, Mockery::mock(ExecutionDao::class));

        $project         = Mockery::mock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T2' => 102,
            'T3' => 103
        ];

        $tracker_checker->shouldReceive('checkTrackerIsInProject')->never();
        $tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')->never();

        $config->shouldReceive('setProjectConfiguration')->never();

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            Mockery::spy(Tracker_XML_Importer_ArtifactImportedMapping::class),
            Mockery::spy(ImportedChangesetMapping::class)
        );
    }

    public function testItThrowsAnExceptionIfAtLeastOneProvidedTrackerIdIsNotUsable(): void
    {
        $config          = Mockery::mock(Config::class);
        $tracker_checker = Mockery::mock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, Mockery::mock(ExecutionDao::class));

        $project         = Mockery::mock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104
        ];

        $tracker_checker->shouldReceive('checkTrackerIsInProject')->times(2);
        $tracker_checker->shouldReceive('checkSubmittedTrackerCanBeUsed')
            ->with($project, 103)
            ->andThrow(TrackerHasAtLeastOneFrozenFieldsPostActionException::class);

        $this->expectException(\Exception::class);

        $config->shouldReceive('setProjectConfiguration')->never();

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            Mockery::spy(Tracker_XML_Importer_ArtifactImportedMapping::class),
            Mockery::spy(ImportedChangesetMapping::class)
        );
    }
}
