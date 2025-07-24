<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\XML;

use PFUser;
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupSaver;
use Tuleap\Timetracking\Time\TimeDao;
use Tuleap\Tracker\Tracker;
use UGroupManager;
use User\XML\Import\IFindUserFromXMLReference;
use XML_RNGValidator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private XML_RNGValidator $rng_validator;
    /**
     * @var TimetrackingEnabler&\PHPUnit\Framework\MockObject\MockObject
     */
    private $timetracking_enabler;
    /**
     * @var TimetrackingUgroupSaver&\PHPUnit\Framework\MockObject\MockObject
     */
    private $timetracking_ugroup_saver;
    /**
     * @var UGroupManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $ugroup_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&IFindUserFromXMLReference
     */
    private $user_finder;
    /**
     * @var TimeDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $time_dao;
    /**
     * @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;
    private XMLImport $xml_import;
    /**
     * @var Project&\PHPUnit\Framework\MockObject\MockObject
     */
    private $project;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->rng_validator             = new XML_RNGValidator();
        $this->timetracking_enabler      = $this->createMock(TimetrackingEnabler::class);
        $this->timetracking_ugroup_saver = $this->createMock(TimetrackingUgroupSaver::class);
        $this->ugroup_manager            = $this->createMock(UGroupManager::class);
        $this->user_finder               = $this->createMock(IFindUserFromXMLReference::class);
        $this->time_dao                  = $this->createMock(TimeDao::class);
        $this->logger                    = $this->createMock(LoggerInterface::class);

        $this->xml_import = new XMLImport(
            $this->rng_validator,
            $this->timetracking_enabler,
            $this->timetracking_ugroup_saver,
            $this->ugroup_manager,
            $this->user_finder,
            $this->time_dao,
            $this->logger
        );

        $this->project = $this->createMock(Project::class);
    }

    public function testItImportsTimesByXML(): void
    {
        $tracker_mapping          = $this->createMock(Tracker::class);
        $created_trackers_objects = [
            '789' => $tracker_mapping,
        ];

        $artifact_id_mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $artifact_id_mapping->add(152, 9999);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="789">
                        <timetracking is_enabled="true">
                            <permissions>
                                <read>
                                    <ugroup>project_admins</ugroup>
                                </read>
                                <write>
                                    <ugroup>project_members</ugroup>
                                </write>
                            </permissions>
                            <time artifact_id="152">
                                <user format="ldap">102</user>
                                <minutes>60</minutes>
                                <step>Step 01</step>
                                <day format="ISO8601">2021-02-01T16:06:35+01:00</day>
                            </time>
                        </timetracking>
                    </tracker>
                </trackers>
            </project>
            EOS
        );

        $this->timetracking_enabler
            ->expects($this->once())
            ->method('enableTimetrackingForTracker')
            ->with($tracker_mapping);

        $ugroup_project_admins  = new ProjectUGroup(['ugroup_id' => 3]);
        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);

        $this->ugroup_manager
            ->method('getUGroupByName')
            ->with()
            ->willReturnMap([
                [$this->project, 'project_admins', $ugroup_project_admins],
                [$this->project, 'project_members', $ugroup_project_members],
            ]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveReaders')
            ->with($tracker_mapping, [3]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveWriters')
            ->with($tracker_mapping, [4]);

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(123);
        $this->user_finder->method('getUser')
            ->willReturn($user);

        $this->time_dao
            ->expects($this->once())
            ->method('addTime')
            ->with(
                123,
                9999,
                '2021-02-01',
                '60',
                'Step 01'
            );

        $this->mockLogInfo();

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    public function testItImportsTimesWitoutStepByXML(): void
    {
        $tracker_mapping          = $this->createMock(Tracker::class);
        $created_trackers_objects = [
            '789' => $tracker_mapping,
        ];

        $artifact_id_mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $artifact_id_mapping->add(152, 9999);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="789">
                        <timetracking is_enabled="true">
                            <permissions>
                                <read>
                                    <ugroup>project_admins</ugroup>
                                </read>
                                <write>
                                    <ugroup>project_members</ugroup>
                                </write>
                            </permissions>
                            <time artifact_id="152">
                                <user format="ldap">102</user>
                                <minutes>60</minutes>
                                <day format="ISO8601">2021-02-01T16:06:35+01:00</day>
                            </time>
                        </timetracking>
                    </tracker>
                </trackers>
            </project>
            EOS
        );

        $this->timetracking_enabler
            ->expects($this->once())
            ->method('enableTimetrackingForTracker')
            ->with($tracker_mapping);

        $ugroup_project_admins  = new ProjectUGroup(['ugroup_id' => 3]);
        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);

        $this->ugroup_manager
            ->method('getUGroupByName')
            ->with()
            ->willReturnMap([
                [$this->project, 'project_admins', $ugroup_project_admins],
                [$this->project, 'project_members', $ugroup_project_members],
            ]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveReaders')
            ->with($tracker_mapping, [3]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveWriters')
            ->with($tracker_mapping, [4]);

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(123);
        $this->user_finder->method('getUser')
            ->willReturn($user);

        $this->time_dao
            ->expects($this->once())
            ->method('addTime')
            ->with(
                123,
                9999,
                '2021-02-01',
                '60',
                ''
            );

        $this->mockLogInfo();

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    public function testItImportsOnlyConfigurationIfNoTimesProvidedInXML(): void
    {
        $tracker_mapping          = $this->createMock(Tracker::class);
        $created_trackers_objects = [
            '789' => $tracker_mapping,
        ];

        $artifact_id_mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $artifact_id_mapping->add(152, 9999);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="789">
                        <timetracking is_enabled="true">
                            <permissions>
                                <read>
                                    <ugroup>project_admins</ugroup>
                                </read>
                                <write>
                                    <ugroup>project_members</ugroup>
                                </write>
                            </permissions>
                        </timetracking>
                    </tracker>
                </trackers>
            </project>
            EOS
        );

        $this->timetracking_enabler
            ->expects($this->once())
            ->method('enableTimetrackingForTracker')
            ->with($tracker_mapping);

        $ugroup_project_admins  = new ProjectUGroup(['ugroup_id' => 3]);
        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager
            ->method('getUGroupByName')
            ->with()
            ->willReturnMap([
                [$this->project, 'project_admins', $ugroup_project_admins],
                [$this->project, 'project_members', $ugroup_project_members],
            ]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveReaders')
            ->with($tracker_mapping, [3]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveWriters')
            ->with($tracker_mapping, [4]);

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(123);
        $this->user_finder->method('getUser')
            ->willReturn($user);

        $this->time_dao->expects($this->never())->method('addTime');

        $this->mockLogInfo();

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    public function testItImportsOnlyWriteConfigurationIfNoTimesAndReadersProvidedInXML(): void
    {
        $tracker_mapping          = $this->createMock(Tracker::class);
        $created_trackers_objects = [
            '789' => $tracker_mapping,
        ];

        $artifact_id_mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $artifact_id_mapping->add(152, 9999);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="789">
                        <timetracking is_enabled="true">
                            <permissions>
                                <write>
                                    <ugroup>project_members</ugroup>
                                </write>
                            </permissions>
                        </timetracking>
                    </tracker>
                </trackers>
            </project>
            EOS
        );

        $this->timetracking_enabler
            ->expects($this->once())
            ->method('enableTimetrackingForTracker')
            ->with($tracker_mapping);

        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager
            ->expects($this->once())->method('getUGroupByName')
            ->with($this->project, 'project_members')
            ->willReturn($ugroup_project_members);

        $this->timetracking_ugroup_saver->expects($this->never())->method('saveReaders');

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveWriters')
            ->with($tracker_mapping, [4]);

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(123);
        $this->user_finder->method('getUser')
            ->willReturn($user);

        $this->time_dao->expects($this->never())->method('addTime');

        $this->logger
            ->method('info')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        'Enable timetracking for tracker 789.',
                        'Add timetracking writer permission.' => true
                    };
                }
            );

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    public function testItSkipsUnkownUgroupForConfigurationInXML(): void
    {
        $tracker_mapping          = $this->createMock(Tracker::class);
        $created_trackers_objects = [
            '789' => $tracker_mapping,
        ];

        $artifact_id_mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $artifact_id_mapping->add(152, 9999);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project>
                <trackers>
                    <tracker id="789">
                        <timetracking is_enabled="true">
                            <permissions>
                                <read>
                                    <ugroup>project_admins</ugroup>
                                    <ugroup>unkown_ugroup</ugroup>
                                </read>
                                <write>
                                    <ugroup>project_members</ugroup>
                                </write>
                            </permissions>
                        </timetracking>
                    </tracker>
                </trackers>
            </project>
            EOS
        );

        $this->timetracking_enabler
            ->expects($this->once())
            ->method('enableTimetrackingForTracker')
            ->with($tracker_mapping);

        $ugroup_project_admins  = new ProjectUGroup(['ugroup_id' => 3]);
        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager
            ->method('getUGroupByName')
            ->willReturnMap([
                [$this->project, 'project_members', $ugroup_project_members],
                [$this->project, 'project_admins', $ugroup_project_admins],
                [$this->project, 'unkown_ugroup', null],
            ]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveReaders')
            ->with($tracker_mapping, [3]);

        $this->timetracking_ugroup_saver
            ->expects($this->once())
            ->method('saveWriters')
            ->with($tracker_mapping, [4]);

        $user = $this->createMock(PFUser::class);
        $user->method('getId')->willReturn(123);
        $this->user_finder->method('getUser')
            ->willReturn($user);

        $this->mockLogInfo();

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with('Could not find any ugroup named unkown_ugroup, skipping.');

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    public function testItDoesNothingIfTrackerIsNotProvidedInProjectXML(): void
    {
        $tracker_mapping          = $this->createMock(Tracker::class);
        $created_trackers_objects = [
            '789' => $tracker_mapping,
        ];

        $artifact_id_mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $artifact_id_mapping->add(152, 9999);

        $xml = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <project>
            </project>
            EOS
        );

        $this->timetracking_enabler->expects($this->never())->method('enableTimetrackingForTracker');
        $this->ugroup_manager->expects($this->never())->method('getUGroupByName');
        $this->ugroup_manager->expects($this->never())->method('getUGroupByName');
        $this->timetracking_ugroup_saver->expects($this->never())->method('saveReaders');
        $this->timetracking_ugroup_saver->expects($this->never())->method('saveWriters');
        $this->user_finder->expects($this->never())->method('getUser');
        $this->time_dao->expects($this->never())->method('addTime');

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    private function mockLogInfo(): void
    {
        $this->logger
            ->method('info')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        'Enable timetracking for tracker 789.',
                        'Add timetracking reader permission.',
                        'Add timetracking writer permission.' => true
                    };
                }
            );
    }
}
