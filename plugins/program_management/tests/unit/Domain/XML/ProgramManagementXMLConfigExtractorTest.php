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

namespace Tuleap\ProgramManagement\Domain\XML;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Domain\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Stub\RetrieveUGroupsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class ProgramManagementXMLConfigExtractorTest extends TestCase
{
    /**
     * @testWith ["__fixtures/none", false]
     *           ["__fixtures/valid_xml", true]
     */
    public function testIsThereAConfigToImport(string $xml_dir, bool $is_there_a_config): void
    {
        $extractor = new ProgramManagementXMLConfigExtractor(
            RetrieveUGroupsStub::buildWithNoUGroups()
        );

        self::assertEquals($is_there_a_config, $extractor->isThereAConfigToImport(__DIR__ . '/' . $xml_dir));
    }

    public function testItThrowsWhenTheXMLFileIsNotValid(): void
    {
        $this->expectException(\XML_ParseException::class);
        $this->processExtraction(
            __DIR__ . "/__fixtures/invalid_xml",
            [],
            false
        );
    }

    public function testItThrowsWhenTheSourceTrackerReferenceIsNotValid(): void
    {
        $this->expectException(CannotFindSourceTrackerUsingXmlReference::class);
        $this->processExtraction(
            __DIR__ . "/__fixtures/valid_xml",
            [
                'T2' => 102,
                'T3' => 103
            ],
            false
        );
    }

    public function testItThrowsWhenAPlannableTrackerReferenceIsNotValid(): void
    {
        $this->expectException(CannotFindPlannableTrackerInMappingException::class);
        $this->processExtraction(
            __DIR__ . '/__fixtures/valid_xml',
            [
                'T36277' => 277,
                'T36278' => 278,
            ],
            false
        );
    }

    public function testItThrowsWhenAnUgroupReferenceIsNotValid(): void
    {
        $this->expectException(CannotFindUserGroupInProjectException::class);
        $this->processExtraction(
            __DIR__ . '/__fixtures/valid_xml',
            [
                'T36277' => 277,
                'T36280' => 278,
                'T37001' => 279,
            ],
            false
        );
    }

    public function testItExtractsTheXMLConfig(): void
    {
        $config = $this->processExtraction(
            __DIR__ . '/__fixtures/valid_xml',
            [
                'T36277' => 277,
                'T36280' => 278,
                'T37001' => 279,
            ],
            true
        );

        self::assertEquals(277, $config->source_tracker_id);
        self::assertEquals([278, 279], $config->plannable_trackers_ids);
        self::assertEquals(['101_3'], $config->ugroups_that_can_prioritize);
    }

    private function processExtraction(string $xml_extraction_path, array $created_trackers_mapping, bool $build_ugroup_retriever_with_ugroups): ProgramManagementXMLConfig
    {
        if ($build_ugroup_retriever_with_ugroups) {
            $ugroup_retriever = RetrieveUGroupsStub::buildWithUGroups();
        } else {
            $ugroup_retriever = RetrieveUGroupsStub::buildWithNoUGroups();
        }

        $extractor = new ProgramManagementXMLConfigExtractor($ugroup_retriever);
        return $extractor->extractConfigForProgram(
            ProgramForAdministrationIdentifier::fromProject(
                VerifyIsTeamStub::withNotValidTeam(),
                VerifyProjectPermissionStub::withAdministrator(),
                UserTestBuilder::aUser()->build(),
                ProjectTestBuilder::aProject()->withId(101)->build()
            ),
            $xml_extraction_path,
            $created_trackers_mapping
        );
    }
}
