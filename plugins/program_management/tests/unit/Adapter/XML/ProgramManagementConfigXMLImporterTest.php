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

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\XML\ProgramManagementXMLConfig;
use Tuleap\ProgramManagement\Stub\CreatePlanStub;
use Tuleap\ProgramManagement\Stub\ExtractXMLConfigStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class ProgramManagementConfigXMLImporterTest extends TestCase
{
    private CreatePlanStub $plan_creator;

    private TestLogger $logger;

    private \PFUser $current_user;

    protected function setUp(): void
    {
        $this->plan_creator = CreatePlanStub::build();
        $this->current_user = UserTestBuilder::aUser()->build();
        $this->logger       = new TestLogger();
    }

    public function testItDoesNothingWhenThereIsNoConfigFile(): void
    {
        $this->processImport(false, true);

        self::assertTrue($this->logger->hasInfoThatContains('No config to be imported'));
    }

    public function testItAbortsTheImportWhenXMLConfigExtractionHasFailed(): void
    {
        $this->processImport(true, true);

        self::assertTrue($this->logger->hasErrorThatContains('Cannot load XML from path/to/xml'));
    }

    public function testItAbortsTheImportWhenPlanChangeCannotBeCreated(): void
    {
        $this->plan_creator->willThrowExceptionOnPlanChangeCreation();

        $this->processImport(true, false);

        self::assertTrue($this->logger->hasErrorThatContains('PlanChange creation has failed for some reasons ¯\_(ツ)_/¯'));
    }

    public function testItImportsTheConfiguration(): void
    {
        $this->processImport(true, false);

        $last_plan_creation_args = $this->plan_creator->getCreateMethodCallsArgs(0);

        self::assertEquals(10, $last_plan_creation_args->program_increment_change->tracker_id);
        self::assertNull($last_plan_creation_args->program_increment_change->label);
        self::assertNull($last_plan_creation_args->program_increment_change->sub_label);

        self::assertEquals($this->current_user, $last_plan_creation_args->user);
        self::assertEquals(101, $last_plan_creation_args->project_id);
        self::assertEquals([12, 13], $last_plan_creation_args->tracker_ids_that_can_be_planned);
        self::assertEquals(['101_3'], $last_plan_creation_args->can_possibly_prioritize_ugroups);
        self::assertNull($last_plan_creation_args->iteration);

        self::assertTrue($this->logger->hasInfoThatContains('Configuration imported successfully'));
    }

    private function processImport(bool $is_there_a_config_to_import, bool $will_xml_extraction_fail): void
    {
        if ($is_there_a_config_to_import) {
            $xml_config_extractor = ExtractXMLConfigStub::buildWithConfigToImport(
                new ProgramManagementXMLConfig(
                    10,
                    [12, 13],
                    ['101_3']
                )
            );
        } else {
            $xml_config_extractor = ExtractXMLConfigStub::buildWithNoConfigToImport();
        }

        if ($will_xml_extraction_fail) {
            $xml_config_extractor->withFailingExtraction();
        }

        $importer = new ProgramManagementConfigXMLImporter(
            $this->plan_creator,
            $xml_config_extractor,
            $this->logger
        );

        $importer->import(
            ProgramForAdministrationIdentifier::fromProject(
                VerifyIsTeamStub::withNotValidTeam(),
                VerifyProjectPermissionStub::withAdministrator(),
                $this->current_user,
                ProjectTestBuilder::aProject()->withId(101)->build()
            ),
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
