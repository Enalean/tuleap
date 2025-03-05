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

use Project;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneFrozenFieldsPostActionException;
use Tuleap\TestManagement\Administration\TrackerHasAtLeastOneHiddenFieldsetsPostActionException;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\MissingArtifactLinkException;
use Tuleap\Tracker\XML\Importer\ImportedChangesetMapping;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItImportsConfigurationFromXMLContent(): void
    {
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);
        $execution_dao   = $this->createMock(ExecutionDao::class);

        $xml_import = new XMLImport($config, $tracker_checker, $execution_dao);

        $artifact_id_mapping = $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class);
        $artifact_id_mapping
            ->method('containsSource')
            ->willReturnCallback(static fn (string $source_id) => match ($source_id) {
                '123', '124' => true,
            });
        $artifact_id_mapping
            ->method('get')
            ->willReturnCallback(static fn (string $source_id) => match ($source_id) {
                '123' => 1123,
                '124' => 1124,
            });

        $changeset_mapping = $this->createMock(ImportedChangesetMapping::class);
        $changeset_mapping
            ->method('get')
            ->willReturnCallback(static fn (string $name) => match ($name) {
                'CHANGESET_10001' => 11001,
                'CHANGESET_10002' => 11002,
            });

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104,
        ];

        $tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');
        $tracker_checker->expects(self::once())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $tracker_checker->expects(self::once())->method('checkSubmittedExecutionTrackerCanBeUsed');

        $config
            ->expects(self::once())
            ->method('setProjectConfiguration')
            ->with($project, 102, 103, 104, 101);

        $execution_dao
            ->expects(self::exactly(2))
            ->method('updateExecutionToUseSpecificVersionOfDefinition')
            ->willReturnCallback(static fn (
                int $execution_artifact_id,
                int $execution_tracker_id,
                int $definition_changeset_id,
                int $definition_tracker_id,
            ) => match (true) {
                $execution_artifact_id === 1123 && $definition_changeset_id === 11001 => true,
                $execution_artifact_id === 1124 && $definition_changeset_id === 11002 => true,
            });

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
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, $this->createMock(ExecutionDao::class));

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [];

        $tracker_checker->expects(self::never())->method('checkSubmittedTrackerCanBeUsed');
        $tracker_checker->expects(self::never())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $tracker_checker->expects(self::never())->method('checkSubmittedExecutionTrackerCanBeUsed');
        $config->expects(self::never())->method('setProjectConfiguration');

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(ImportedChangesetMapping::class)
        );
    }

    public function testItIDoesNotImportANonProperlySetConfiguration(): void
    {
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, $this->createMock(ExecutionDao::class));

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T2' => 102,
            'T3' => 103,
        ];

        $tracker_checker->expects(self::never())->method('checkSubmittedTrackerCanBeUsed');
        $tracker_checker->expects(self::never())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $tracker_checker->expects(self::never())->method('checkSubmittedExecutionTrackerCanBeUsed');

        $config->expects(self::never())->method('setProjectConfiguration');

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(ImportedChangesetMapping::class)
        );
    }

    public function testItThrowsAnExceptionIfAtLeastOneProvidedTrackerIdIsNotUsable(): void
    {
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, $this->createMock(ExecutionDao::class));

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104,
        ];

        $tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');
        $tracker_checker->method('checkSubmittedDefinitionTrackerCanBeUsed')
            ->with($project, 103)
            ->willThrowException(new TrackerHasAtLeastOneFrozenFieldsPostActionException());

        $this->expectException(\Exception::class);

        $config->expects(self::never())->method('setProjectConfiguration');

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(ImportedChangesetMapping::class)
        );
    }

    public function testItThrowsAnExceptionIfTrackerHasHiddenFieldPostAction(): void
    {
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, $this->createMock(ExecutionDao::class));

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104,
        ];

        $tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');
        $tracker_checker->expects(self::once())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $tracker_checker->method('checkSubmittedExecutionTrackerCanBeUsed')
            ->willThrowException(new TrackerHasAtLeastOneHiddenFieldsetsPostActionException());

        $this->expectException(\Exception::class);

        $config->expects(self::never())->method('setProjectConfiguration');

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(ImportedChangesetMapping::class)
        );
    }

    public function testItThrowsAnExceptionIfATrackerHasArtifactLinkField(): void
    {
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, $this->createMock(ExecutionDao::class));

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104,
        ];

        $tracker_checker
            ->expects(self::once())
            ->method('checkSubmittedTrackerCanBeUsed')
            ->willThrowException(new MissingArtifactLinkException());
        $tracker_checker->expects(self::never())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $tracker_checker->expects(self::never())->method('checkSubmittedExecutionTrackerCanBeUsed');

        $this->expectException(\Exception::class);

        $config->expects(self::never())->method('setProjectConfiguration');

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(ImportedChangesetMapping::class)
        );
    }

    public function testItThrowsAnExceptionIfDefinitionTrackerHasNotStepDefinitionField(): void
    {
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, $this->createMock(ExecutionDao::class));

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104,
        ];

        $tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');
        $tracker_checker->expects(self::once())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $tracker_checker->method('checkSubmittedExecutionTrackerCanBeUsed')
            ->willThrowException(new TrackerHasAtLeastOneHiddenFieldsetsPostActionException());

        $this->expectException(\Exception::class);

        $config->expects(self::never())->method('setProjectConfiguration');

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(ImportedChangesetMapping::class)
        );
    }

    public function testItThrowsAnExceptionIfExecutionTrackerHasNotStepExecutionField(): void
    {
        $config          = $this->createMock(Config::class);
        $tracker_checker = $this->createMock(TrackerChecker::class);

        $xml_import = new XMLImport($config, $tracker_checker, $this->createMock(ExecutionDao::class));

        $project         = $this->createMock(Project::class);
        $extraction_path = __DIR__ . '/_fixtures';
        $tracker_mapping = [
            'T1' => 101,
            'T2' => 102,
            'T3' => 103,
            'T4' => 104,
        ];

        $tracker_checker->expects(self::exactly(2))->method('checkSubmittedTrackerCanBeUsed');
        $tracker_checker->expects(self::once())->method('checkSubmittedDefinitionTrackerCanBeUsed');
        $tracker_checker->method('checkSubmittedExecutionTrackerCanBeUsed')
            ->willThrowException(new TrackerHasAtLeastOneHiddenFieldsetsPostActionException());

        $this->expectException(\Exception::class);

        $config->expects(self::never())->method('setProjectConfiguration');

        $xml_import->import(
            $project,
            $extraction_path,
            $tracker_mapping,
            $this->createMock(Tracker_XML_Importer_ArtifactImportedMapping::class),
            $this->createMock(ImportedChangesetMapping::class)
        );
    }
}
