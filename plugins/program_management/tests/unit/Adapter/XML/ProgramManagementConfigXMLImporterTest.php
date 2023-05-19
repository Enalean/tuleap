<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\XML;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CreatePlanStub;
use Tuleap\ProgramManagement\Tests\Stub\ExtractXMLConfigStub;
use Tuleap\ProgramManagement\Tests\Stub\ParseXMLConfigStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ProgramManagementConfigXMLImporterTest extends TestCase
{
    private CreatePlanStub $plan_creator;
    private TestLogger $logger;
    private UserReference $current_user;

    protected function setUp(): void
    {
        $this->plan_creator = CreatePlanStub::build();
        $this->current_user = UserReferenceStub::withDefaults();
        $this->logger       = new TestLogger();
    }

    public function testItDoesNothingWhenThereIsNoConfigFile(): void
    {
        $this->processImport(false, true, true);

        self::assertTrue($this->logger->hasInfoThatContains('No config to be imported'));
    }

    public function testItAbortsTheImportWhenXMLConfigParsingHasFailed(): void
    {
        $this->processImport(true, true, true);

        self::assertTrue($this->logger->hasErrorThatContains('Cannot load XML from path/to/xml'));
    }

    public function testItAbortsTheImportWhenPlanChangeCannotBeCreated(): void
    {
        $this->plan_creator->willThrowExceptionOnPlanChangeCreation();

        $this->processImport(true, false, false);

        self::assertTrue(
            $this->logger->hasErrorThatContains('PlanChange creation has failed for some reasons ¯\_(ツ)_/¯')
        );
    }

    public function testItImportsTheConfiguration(): void
    {
        $this->processImport(true, false, false);

        $last_plan_creation_args = $this->plan_creator->getCreateMethodCallsArgs(0);

        self::assertEquals(10, $last_plan_creation_args->program_increment_change->tracker_id);
        self::assertEquals("Crémants d'Alsace", $last_plan_creation_args->program_increment_change->label);
        self::assertEquals('Crémant', $last_plan_creation_args->program_increment_change->sub_label);

        self::assertEquals($this->current_user, $last_plan_creation_args->user);
        self::assertEquals(101, $last_plan_creation_args->project_id);
        self::assertEquals([12, 13], $last_plan_creation_args->tracker_ids_that_can_be_planned);
        self::assertEquals(['101_3'], $last_plan_creation_args->can_possibly_prioritize_ugroups);
        self::assertEquals(14, $last_plan_creation_args->iteration?->tracker_id);
        self::assertEquals("Rations de survie", $last_plan_creation_args->iteration?->label);
        self::assertEquals("ration", $last_plan_creation_args->iteration?->sub_label);

        self::assertTrue($this->logger->hasInfoThatContains('Configuration imported successfully'));
    }

    private function processImport(
        bool $is_there_a_config_to_import,
        bool $will_xml_parsing_fail,
        bool $will_xml_extraction_fail,
    ): void {
        if ($is_there_a_config_to_import) {
            $xml_config_parser    = ParseXMLConfigStub::buildWithConfigFile();
            $xml_config_extractor = ExtractXMLConfigStub::buildWithConfigToImport(
                10,
                [12, 13],
                ['101_3'],
                "Crémants d'Alsace",
                "Crémant",
                14,
                "Rations de survie",
                "ration"
            );
        } else {
            $xml_config_parser    = ParseXMLConfigStub::buildWithNoConfigFile();
            $xml_config_extractor = ExtractXMLConfigStub::buildWithNoConfigToImport();
        }

        if ($will_xml_parsing_fail) {
            $xml_config_parser->withFailingParsing();
        }

        if ($will_xml_extraction_fail) {
            $xml_config_extractor->withFailingExtraction();
        }

        $importer = new ProgramManagementConfigXMLImporter(
            $this->plan_creator,
            $xml_config_parser,
            $xml_config_extractor,
            $this->logger
        );

        $importer->import(
            ProgramForAdministrationIdentifierBuilder::build(),
            'path/to/xml',
            [
                'T10' => 10,
                'T12' => 12,
                'T13' => 13,
            ],
            $this->current_user
        );
    }
}
