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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\Timetracking\Admin\TimetrackingEnabler;
use Tuleap\Timetracking\Admin\TimetrackingUgroupSaver;
use Tuleap\Timetracking\Time\TimeDao;
use UGroupManager;
use User\XML\Import\IFindUserFromXMLReference;
use XML_RNGValidator;

final class XMLImportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLImport
     */
    private $xml_import;

    /**
     * @var XML_RNGValidator
     */
    private $rng_validator;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TimetrackingEnabler
     */
    private $timetracking_enabler;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TimetrackingUgroupSaver
     */
    private $timetracking_ugroup_saver;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|IFindUserFromXMLReference
     */
    private $user_finder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TimeDao
     */
    private $time_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rng_validator             = new XML_RNGValidator();
        $this->timetracking_enabler      = Mockery::mock(TimetrackingEnabler::class);
        $this->timetracking_ugroup_saver = Mockery::mock(TimetrackingUgroupSaver::class);
        $this->ugroup_manager            = Mockery::mock(UGroupManager::class);
        $this->user_finder               = Mockery::mock(IFindUserFromXMLReference::class);
        $this->time_dao                  = Mockery::mock(TimeDao::class);
        $this->logger                    = Mockery::mock(LoggerInterface::class);

        $this->xml_import = new XMLImport(
            $this->rng_validator,
            $this->timetracking_enabler,
            $this->timetracking_ugroup_saver,
            $this->ugroup_manager,
            $this->user_finder,
            $this->time_dao,
            $this->logger
        );

        $this->project = Mockery::mock(Project::class);
    }

    public function testItImportsTimesByXML(): void
    {
        $tracker_mapping          = Mockery::mock(Tracker::class);
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

        $this->timetracking_enabler->shouldReceive('enableTimetrackingForTracker')
            ->once()
            ->with($tracker_mapping);

        $ugroup_project_admins = new ProjectUGroup(['ugroup_id' => 3]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_admins")
            ->once()
            ->andReturn($ugroup_project_admins);

        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_members")
            ->once()
            ->andReturn($ugroup_project_members);

        $this->timetracking_ugroup_saver->shouldReceive('saveReaders')
            ->with($tracker_mapping, [3])
            ->once();

        $this->timetracking_ugroup_saver->shouldReceive('saveWriters')
            ->with($tracker_mapping, [4])
            ->once();

        $user = Mockery::mock(PFUser::class)->shouldReceive('getId')->andReturn(123)->getMock();
        $this->user_finder->shouldReceive('getUser')
            ->andReturn($user);

        $this->time_dao->shouldReceive('addTime')
            ->once()
            ->with(
                123,
                9999,
                "2021-02-01",
                "60",
                "Step 01"
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
        $tracker_mapping          = Mockery::mock(Tracker::class);
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

        $this->timetracking_enabler->shouldReceive('enableTimetrackingForTracker')
            ->once()
            ->with($tracker_mapping);

        $ugroup_project_admins = new ProjectUGroup(['ugroup_id' => 3]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_admins")
            ->once()
            ->andReturn($ugroup_project_admins);

        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_members")
            ->once()
            ->andReturn($ugroup_project_members);

        $this->timetracking_ugroup_saver->shouldReceive('saveReaders')
            ->with($tracker_mapping, [3])
            ->once();

        $this->timetracking_ugroup_saver->shouldReceive('saveWriters')
            ->with($tracker_mapping, [4])
            ->once();

        $user = Mockery::mock(PFUser::class)->shouldReceive('getId')->andReturn(123)->getMock();
        $this->user_finder->shouldReceive('getUser')
            ->andReturn($user);

        $this->time_dao->shouldReceive('addTime')
            ->once()
            ->with(
                123,
                9999,
                "2021-02-01",
                "60",
                ""
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
        $tracker_mapping          = Mockery::mock(Tracker::class);
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

        $this->timetracking_enabler->shouldReceive('enableTimetrackingForTracker')
            ->once()
            ->with($tracker_mapping);

        $ugroup_project_admins = new ProjectUGroup(['ugroup_id' => 3]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_admins")
            ->once()
            ->andReturn($ugroup_project_admins);

        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_members")
            ->once()
            ->andReturn($ugroup_project_members);

        $this->timetracking_ugroup_saver->shouldReceive('saveReaders')
            ->with($tracker_mapping, [3])
            ->once();

        $this->timetracking_ugroup_saver->shouldReceive('saveWriters')
            ->with($tracker_mapping, [4])
            ->once();

        $user = Mockery::mock(PFUser::class)->shouldReceive('getId')->andReturn(123)->getMock();
        $this->user_finder->shouldReceive('getUser')
            ->andReturn($user);

        $this->time_dao->shouldNotReceive('addTime');

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
        $tracker_mapping          = Mockery::mock(Tracker::class);
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

        $this->timetracking_enabler->shouldReceive('enableTimetrackingForTracker')
            ->once()
            ->with($tracker_mapping);

        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_members")
            ->once()
            ->andReturn($ugroup_project_members);

        $this->timetracking_ugroup_saver->shouldNotReceive('saveReaders');

        $this->timetracking_ugroup_saver->shouldReceive('saveWriters')
            ->with($tracker_mapping, [4])
            ->once();

        $user = Mockery::mock(PFUser::class)->shouldReceive('getId')->andReturn(123)->getMock();
        $this->user_finder->shouldReceive('getUser')
            ->andReturn($user);

        $this->time_dao->shouldNotReceive('addTime');

        $this->logger->shouldReceive('info')
            ->with('Enable timetracking for tracker 789.')
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Add timetracking writer permission.')
            ->once();

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    public function testItSkipsUnkownUgroupForConfigurationInXML(): void
    {
        $tracker_mapping          = Mockery::mock(Tracker::class);
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

        $this->timetracking_enabler->shouldReceive('enableTimetrackingForTracker')
            ->once()
            ->with($tracker_mapping);

        $ugroup_project_admins = new ProjectUGroup(['ugroup_id' => 3]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_admins")
            ->once()
            ->andReturn($ugroup_project_admins);

        $ugroup_project_members = new ProjectUGroup(['ugroup_id' => 4]);
        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "project_members")
            ->once()
            ->andReturn($ugroup_project_members);

        $this->ugroup_manager->shouldReceive('getUGroupByName')
            ->with($this->project, "unkown_ugroup")
            ->once()
            ->andReturnNull();

        $this->timetracking_ugroup_saver->shouldReceive('saveReaders')
            ->with($tracker_mapping, [3])
            ->once();

        $this->timetracking_ugroup_saver->shouldReceive('saveWriters')
            ->with($tracker_mapping, [4])
            ->once();

        $user = Mockery::mock(PFUser::class)->shouldReceive('getId')->andReturn(123)->getMock();
        $this->user_finder->shouldReceive('getUser')
            ->andReturn($user);

        $this->mockLogInfo();

        $this->logger->shouldReceive('warning')
            ->with('Could not find any ugroup named unkown_ugroup, skipping.')
            ->once();

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    public function testItDoesNothingIfTrackerIsNotProvidedInProjectXML(): void
    {
        $tracker_mapping          = Mockery::mock(Tracker::class);
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

        $this->timetracking_enabler->shouldNotReceive('enableTimetrackingForTracker');
        $this->ugroup_manager->shouldNotReceive('getUGroupByName');
        $this->ugroup_manager->shouldNotReceive('getUGroupByName');
        $this->timetracking_ugroup_saver->shouldNotReceive('saveReaders');
        $this->timetracking_ugroup_saver->shouldNotReceive('saveWriters');
        $this->user_finder->shouldNotReceive('getUser');
        $this->time_dao->shouldNotReceive('addTime');

        $this->xml_import->import(
            $xml,
            $this->project,
            $created_trackers_objects,
            $artifact_id_mapping
        );
    }

    private function mockLogInfo(): void
    {
        $this->logger->shouldReceive('info')
            ->with('Enable timetracking for tracker 789.')
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Add timetracking reader permission.')
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Add timetracking writer permission.')
            ->once();
    }
}
