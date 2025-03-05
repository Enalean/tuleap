<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\XML;

use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindPlannableTrackerInMappingException;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindSourceTrackerUsingXmlReference;
use Tuleap\ProgramManagement\Adapter\XML\Exceptions\CannotFindUserGroupInProjectException;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUGroupsStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramManagementXMLConfigExtractorTest extends TestCase
{
    public function testItThrowsWhenTheSourceTrackerReferenceIsNotValid(): void
    {
        $this->expectException(CannotFindSourceTrackerUsingXmlReference::class);

        $extractor = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());
        $extractor->getIncrementsSourceTrackerId(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <increments>
                        <source_tracker REF="T123"/>
                    </increments>
                </program_management>'
            ),
            [
                'T2' => 102,
                'T3' => 103,
            ]
        );
    }

    public function testItReturnsTheSourceTrackerId(): void
    {
        $extractor         = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());
        $source_tracker_id = $extractor->getIncrementsSourceTrackerId(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <increments>
                        <source_tracker REF="T123"/>
                    </increments>
                </program_management>'
            ),
            [
                'T2' => 102,
                'T3' => 103,
                'T123' => 123,
            ]
        );

        self::assertEquals(123, $source_tracker_id);
    }

    public function testItThrowsWhenAPlannableTrackerReferenceIsNotValid(): void
    {
        $this->expectException(CannotFindPlannableTrackerInMappingException::class);

        $extractor = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());
        $extractor->getIncrementsPlannableTrackersIds(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <increments>
                        <plannable_trackers>
                            <plannable_tracker REF="T2"/>
                            <plannable_tracker REF="T4"/>
                        </plannable_trackers>
                    </increments>
                </program_management>'
            ),
            [
                'T2' => 102,
                'T3' => 103,
            ]
        );
    }

    public function testItReturnsTheArrayOfPlannableTrackersIds(): void
    {
        $extractor              = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());
        $plannable_trackers_ids = $extractor->getIncrementsPlannableTrackersIds(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <increments>
                        <plannable_trackers>
                            <plannable_tracker REF="T2"/>
                            <plannable_tracker REF="T4"/>
                        </plannable_trackers>
                    </increments>
                </program_management>'
            ),
            [
                'T2' => 102,
                'T4' => 104,
            ]
        );

        self::assertSame([102, 104], $plannable_trackers_ids);
    }

    public function testItThrowsWhenAnUgroupReferenceIsNotValid(): void
    {
        $this->expectException(CannotFindUserGroupInProjectException::class);

        $extractor = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithNoUGroups());
        $extractor->getUgroupsIdsThatCanPrioritizeIncrements(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <increments>
                        <can_prioritize>
                            <ugroup ugroup_name="metallica"/>
                        </can_prioritize>
                    </increments>
                </program_management>'
            ),
            ProgramForAdministrationIdentifierBuilder::build()
        );
    }

    public function testItReturnsTheArrayOfProjectUgroupsThatCanPrioritize(): void
    {
        $extractor = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());
        $ugroups   = $extractor->getUgroupsIdsThatCanPrioritizeIncrements(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <increments>
                        <can_prioritize>
                            <ugroup ugroup_name="project_members"/>
                        </can_prioritize>
                    </increments>
                </program_management>'
            ),
            ProgramForAdministrationIdentifierBuilder::build()
        );

        self::assertSame(['101_3'], $ugroups);
    }

    public function testItDoesNothingWhenThereAreNoCustomisations(): void
    {
        $xml_config = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <increments/>
            </program_management>
        ');
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertNull($extractor->getCustomProgramIncrementsSectionName($xml_config));
        self::assertNull($extractor->getCustomProgramIncrementsMilestonesName($xml_config));
    }

    public function testItReturnsNullWhenThereIsNoCustomPISectionName(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <increments>
                    <milestones_name>Bar</milestones_name>
                </increments>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertNull($extractor->getCustomProgramIncrementsSectionName($xml_config));
    }

    public function testItReturnsTheCustomPISectionName(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <increments>
                    <section_name>Foo</section_name>
                </increments>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertEquals('Foo', $extractor->getCustomProgramIncrementsSectionName($xml_config));
    }

    public function testItReturnsNullWhenThereIsNoCustomPIMilestonesName(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <increments>
                    <section_name>Foo</section_name>
                </increments>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertNull($extractor->getCustomProgramIncrementsMilestonesName($xml_config));
    }

    public function testItThrowsWhenTheSourceIterationTrackerReferenceIsNotValid(): void
    {
        $this->expectException(CannotFindSourceTrackerUsingXmlReference::class);

        $extractor = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());
        $extractor->getIterationsSourceTrackerId(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <iterations>
                        <source_tracker REF="T123"/>
                    </iterations>
                </program_management>'
            ),
            [
                'T2' => 102,
                'T3' => 103,
            ]
        );
    }

    public function testItReturnsTheSourceIterationTrackerId(): void
    {
        $extractor         = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());
        $source_tracker_id = $extractor->getIterationsSourceTrackerId(
            new \SimpleXMLElement(
                '<?xml version="1.0" encoding="UTF-8"?>
                <program_management>
                    <iterations>
                        <source_tracker REF="T123"/>
                    </iterations>
                </program_management>'
            ),
            [
                'T2' => 102,
                'T3' => 103,
                'T123' => 123,
            ]
        );

        self::assertEquals(123, $source_tracker_id);
    }

    public function testItReturnsNullWhenThereAreNoIterationConfiguration(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertNull($extractor->getIterationsSourceTrackerId($xml_config, ['T2' => 102, 'T3' => 103]));
        self::assertNull($extractor->getCustomIterationsMilestonesName($xml_config));
        self::assertNull($extractor->getCustomIterationsMilestonesName($xml_config));
    }

    public function testItReturnsNullWhenThereAreNoIterationLabelsCustomisation(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <iterations>
                    <source_tracker REF="T1234"/>
                </iterations>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertNull($extractor->getCustomIterationsSectionName($xml_config));
        self::assertNull($extractor->getCustomIterationsMilestonesName($xml_config));
    }

    public function testItReturnsTheCustomIterationSectionName(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <iterations>
                    <section_name>Foo</section_name>
                </iterations>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertEquals('Foo', $extractor->getCustomIterationsSectionName($xml_config));
    }

    public function testItReturnsTheCustomIterationMilestonesName(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <iterations>
                    <milestones_name>Bar</milestones_name>
                </iterations>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertEquals('Bar', $extractor->getCustomIterationsMilestonesName($xml_config));
    }

    public function testItReturnsTheCustomPIMilestonesName(): void
    {
        $xml_config = new \SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
            <program_management>
                <increments>
                    <milestones_name>Bar</milestones_name>
                </increments>
            </program_management>
        '
        );
        $extractor  = new ProgramManagementXMLConfigExtractor(RetrieveUGroupsStub::buildWithUGroups());

        self::assertSame('Bar', $extractor->getCustomProgramIncrementsMilestonesName($xml_config));
    }
}
