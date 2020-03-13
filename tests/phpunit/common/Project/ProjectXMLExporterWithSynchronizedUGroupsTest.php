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

declare(strict_types=1);

namespace Tuleap\Project;

use EventManager;
use Mockery as M;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use ProjectXMLExporter;
use Psr\Log\LoggerInterface;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Test\Builders as B;
use UGroupManager;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;

class ProjectXMLExporterWithSynchronizedUGroupsTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $event_manager;
    private $ugroup_manager;
    private $project;
    private $xml_exporter;
    /**
     * @var string
     */
    private $export_dir;
    private $user;
    private $options;
    private $archive;
    /**
     * @var M\MockInterface|SynchronizedProjectMembershipDetector
     */
    private $synch_detector;

    protected function setUp() : void
    {
        $this->event_manager  = M::spy(EventManager::class);
        $this->ugroup_manager = M::spy(UGroupManager::class);
        $xml_validator        = new XML_RNGValidator();
        $user_xml_exporter    = new UserXMLExporter(M::spy(UserManager::class), M::spy(UserXMLExportedCollection::class));
        $this->project        = M::spy(Project::class, ['getPublicName' => 'Project01']);
        $this->synch_detector   = M::mock(SynchronizedProjectMembershipDetector::class);

        $this->xml_exporter   = new ProjectXMLExporter(
            $this->event_manager,
            $this->ugroup_manager,
            $xml_validator,
            $user_xml_exporter,
            $this->synch_detector,
            M::spy(LoggerInterface::class)
        );

        $this->options = array(
            'tracker_id' => 10
        );
        $this->export_dir = "__fixtures";

        $this->archive = M::spy(\Tuleap\Project\XML\Export\ArchiveInterface::class);
        $this->user    = M::spy(PFUser::class);
    }

    public function testItExportsThatUserGroupsAreSynchronizedWithProjectMembers() : void
    {
        $this->synch_detector->shouldReceive('isSynchronizedWithProjectMembers')->with($this->project)->andReturnTrue();

        $user_1 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();

        $project_ugroup_project_admins = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_ADMIN],
                'getMembers' => [$user_1],
            ]
        );

        $project_ugroup_project_members = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
                'getMembers' => [$user_1],
            ]
        );

        $this->ugroup_manager->shouldReceive('getProjectAdminsUGroup')->with($this->project)->andReturns($project_ugroup_project_admins);
        $this->ugroup_manager->shouldReceive('getProjectMembersUGroup')->with($this->project)->andReturns($project_ugroup_project_members);

        $this->ugroup_manager->shouldReceive('getStaticUGroups')->andReturns([]);

        $this->project->shouldReceive('getServices')->andReturns(array());

        $this->event_manager->shouldReceive('processEvent')->once();

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertNotNull($xml_objet->ugroups);
        $this->assertEquals('synchronized', (string) $xml_objet->ugroups['mode']);
    }

    public function testItExportsThatUserGroupsAreNotSynchronizedWithProjectMembers()
    {
        $this->synch_detector->shouldReceive('isSynchronizedWithProjectMembers')->with($this->project)->andReturnFalse();

        $user_1 = B\UserTestBuilder::aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();

        $project_ugroup_project_admins = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_ADMIN],
                'getMembers' => [$user_1],
            ]
        );

        $project_ugroup_project_members = M::spy(
            ProjectUGroup::class,
            [
                'getNormalizedName' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
                'getMembers' => [$user_1],
            ]
        );

        $this->ugroup_manager->shouldReceive('getProjectAdminsUGroup')->with($this->project)->andReturns($project_ugroup_project_admins);
        $this->ugroup_manager->shouldReceive('getProjectMembersUGroup')->with($this->project)->andReturns($project_ugroup_project_members);

        $this->ugroup_manager->shouldReceive('getStaticUGroups')->andReturns([]);

        $this->project->shouldReceive('getServices')->andReturns([]);

        $this->event_manager->shouldReceive('processEvent');

        $xml       = $this->xml_exporter->export($this->project, $this->options, $this->user, $this->archive, $this->export_dir);
        $xml_objet = simplexml_load_string($xml);

        $this->assertNotNull($xml_objet->ugroups);
        $this->assertTrue(! isset($xml_objet->ugroups['mode']));
    }
}
