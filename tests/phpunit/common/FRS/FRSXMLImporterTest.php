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

use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\UploadedLinksUpdater;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class FRSPackageFactoryMock extends FRSPackageFactory
{
    // bypass it for the tests as it calls global functions which access to the db
    public function setDefaultPermissions(FRSPackage $package)
    {
    }

    protected function setLicenseAgreementAtPackageCreation(FRSPackage $package, ?int $original_approval_license)
    {
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotCamelCaps
class FRSXMLImporterTest_FRSFileFactory extends FRSFileFactory
{
    public function __construct()
    {
        parent::__construct();
        $this->setFileForge(['/bin/true']);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses
class FRSXMLImporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var \Tuleap\FRS\UploadedLinksDao
     */
    protected $link_dao;
    protected $frs_permission_creator;

    protected function setUp(): void
    {
        $this->package_factory = new FRSPackageFactoryMock();
        $this->release_factory = \Mockery::mock(\FRSReleaseFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->file_factory = new FRSXMLImporterTest_FRSFileFactory();

        $this->package_dao = \Mockery::spy(\FRSPackageDao::class);
        $this->package_factory->dao = $this->package_dao;
        FRSPackageFactory::setInstance($this->package_factory);

        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);

        $this->release_dao = \Mockery::spy(\FRSReleaseDao::class);
        $this->release_factory->dao =  $this->release_dao;
        $this->release_factory->package_factory = $this->package_factory;
        $this->release_factory->file_factory = $this->file_factory;
        FRSReleaseFactory::setInstance($this->release_factory);

        $this->file_dao = \Mockery::spy(\FRSFileDao::class);
        $this->file_factory->dao = $this->file_dao;
        $this->file_factory->release_factory = $this->release_factory;

        $this->processor_dao = \Mockery::spy(\FRSProcessorDao::class);
        $this->filetype_dao = \Mockery::spy(\FRSFileTypeDao::class);

        $this->user_finder = \Mockery::spy(\User\XML\Import\IFindUserFromXMLReference::class);
        $this->user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($this->user_manager);

        $this->ugroup_dao = \Mockery::spy(\UGroupDao::class);
        $this->ugroup_dao->shouldReceive('searchByGroupIdAndName')->andReturns(new DataAccessResultEmpty());

        $this->xml_import_helper = \Mockery::spy(\XMLImportHelper::class);
        $this->frs_permission_creator = \Mockery::spy(\Tuleap\FRS\FRSPermissionCreator::class);

        $this->link_dao = \Mockery::spy(\Tuleap\FRS\UploadedLinksDao::class);
        $links_updater  = new UploadedLinksUpdater($this->link_dao, \Mockery::spy(\FRSLog::class));

        $this->frs_importer = new FRSXMLImporter(
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            $this->package_factory,
            $this->release_factory,
            $this->file_factory,
            $this->user_finder,
            new UGroupManager($this->ugroup_dao),
            $this->xml_import_helper,
            $this->frs_permission_creator,
            $links_updater,
            $this->processor_dao,
            $this->filetype_dao
        );

        EventManager::setInstance(Mockery::spy(EventManager::class));
        if (isset($GLOBALS['ftp_incoming_dir'])) {
            $this->old_ftp_incoming_dir = $GLOBALS['ftp_incoming_dir'];
        }
        if (isset($GLOBALS['old_ftp_frs_dir_prefix'])) {
            $this->old_ftp_frs_dir_prefix = $GLOBALS['ftp_frs_dir_prefix'];
        }
        $GLOBALS['ftp_incoming_dir'] = $this->getTmpDir();
        $GLOBALS['ftp_frs_dir_prefix'] = $this->getTmpDir();
    }

    protected function tearDown(): void
    {
        FRSPackageFactory::clearInstance();
        ProjectManager::clearInstance();
        FRSReleaseFactory::clearInstance();
        PermissionsManager::clearInstance();
        UserManager::clearInstance();
        EventManager::clearInstance();
        if (isset($this->old_ftp_incoming_dir)) {
            $GLOBALS['ftp_incoming_dir'] = $this->old_ftp_incoming_dir;
        } else {
            unset($GLOBALS['ftp_incoming_dir']);
        }
        if (isset($this->old_ftp_frs_dir_prefix)) {
            $GLOBALS['ftp_frs_dir_prefix'] = $this->old_ftp_frs_dir_prefix;
        } else {
            unset($GLOBALS['ftp_frs_dir_prefix']);
        }
        parent::tearDown();
    }

    public function testItShouldImportOnePackageWithDefaultValues()
    {
        $pm = ProjectManager::instance();
        $project = $pm->getProjectFromDbRow(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml = <<<XML
        <project>
            <frs>
                <package name="empty_package">
                    <read-access><ugroup>project_members</ugroup></read-access>
                </package>
            </frs>
        </project>
XML;
        $xml_element = new SimpleXMLElement($xml);
        $expected_package_array = $this->getDefaultPackage('empty_package');
        $this->package_dao->shouldReceive('createFromArray')->with($expected_package_array)->once();

        $frs_mapping = array();
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);
    }

    public function testItShouldImportPermissions()
    {
        $pm = ProjectManager::instance();
        $project = $pm->getProjectFromDbRow(array('group_id' => 123, 'unix_group_name' => 'test_project'));

        $this->frs_permission_creator->shouldReceive('savePermissions')->with($project, array(2), FRSPermission::FRS_READER)->ordered();
        $this->frs_permission_creator->shouldReceive('savePermissions')->with($project, array(3), FRSPermission::FRS_ADMIN)->ordered();

        $xml = <<<XML
        <project>
            <frs>
                <read-access>
                    <ugroup>registered_users</ugroup>
                </read-access>
                <admin-access>
                    <ugroup>project_members</ugroup>
                </admin-access>
            </frs>
        </project>
XML;

        $xml_element = new SimpleXMLElement($xml);
        $frs_mapping = array();
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);
    }

    public function testItShouldImportOnePackageWithOneRelease()
    {
        $pm = ProjectManager::instance();
        $project = $pm->getProjectFromDbRow(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml = <<<XML
        <project>
            <frs>
                <package name="package">
                    <read-access><ugroup>project_members</ugroup></read-access>
                    <release name="release" time="2015-12-03T14:55:00" preformatted="false">
                        <read-access><ugroup>project_members</ugroup></read-access>
                        <notes>some notes</notes>
                        <changes>some changes</changes>
                        <user format="username">toto</user>
                    </release>
                </package>
            </frs>
        </project>
XML;
        $xml_element = new SimpleXMLElement($xml);

        $user_id = 42;
        $this->user_finder->shouldReceive('getUser')->andReturns(new PFUser(array('user_id' => $user_id)));

        $expected_package_array = $this->getDefaultPackage('package');
        $package_id = 1337;
        $this->package_dao->shouldReceive('createFromArray')->with($expected_package_array)->andReturns($package_id);

        $expected_release_array = array(
            'release_id' => 0,
            'package_id' => $package_id,
            'name' => 'release',
            'notes' => 'some notes',
            'changes' => 'some changes',
            'status_id' => FRSRelease::STATUS_ACTIVE,
            'preformatted' => false,
            'release_date' => strtotime('2015-12-03T14:55:00'),
            'released_by' => $user_id);
        $this->release_dao->shouldReceive('createFromArray')->with($expected_release_array)->once();

        $frs_mapping = array();
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);
    }

    public function testItShouldImportOnePackageWithOneReleaseLinkedToAnArtifact()
    {
        $pm = ProjectManager::instance();
        $project = $pm->getProjectFromDbRow(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml = <<<XML
        <project>
            <frs>
                <package name="package">
                    <read-access><ugroup>project_members</ugroup></read-access>
                    <release name="release" time="2015-12-03T14:55:00" preformatted="false" artifact_id="A101">
                        <read-access><ugroup>project_members</ugroup></read-access>
                        <notes>some notes</notes>
                        <changes>some changes</changes>
                        <user format="username">toto</user>
                    </release>
                </package>
            </frs>
        </project>
XML;

        $xml_element = new SimpleXMLElement($xml);

        $user_id    = 42;
        $package_id = 1337;
        $release    = \Mockery::spy(FRSRelease::class);

        $release->shouldReceive('getGroupId')->andReturn(123);
        $this->user_finder->shouldReceive('getUser')->andReturns(new PFUser(array('user_id' => $user_id)));
        $this->package_dao->shouldReceive('createFromArray')->andReturns($package_id);
        $this->release_dao->shouldReceive('createFromArray')->andReturns(47);
        $this->release_factory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);

        $frs_mapping = array();
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);

        $this->assertSame($frs_mapping[47], 'A101');
    }

    public function testItShouldImportOnePackageWithOneReleaseWithOneFile(): void
    {
        $extraction_path = $this->getTmpDir();
        $temp_file = tempnam($extraction_path, 'thefile_');
        fwrite(fopen($temp_file, 'w+'), 'such file, wow');
        $file_name = basename($temp_file);

        $pm = ProjectManager::instance();
        $project = $pm->getProjectFromDbRow(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml = <<<XML
        <project>
            <frs>
                <package name="package">
                    <read-access><ugroup>project_members</ugroup></read-access>
                    <release name="release" time="2015-12-03T14:55:00" preformatted="false">
                        <read-access><ugroup>project_members</ugroup></read-access>
                        <notes>some notes</notes>
                        <changes>some changes</changes>
                        <user format="username">toto</user>
                        <file src="$file_name" name="lefichier" release-time="2015-12-03T16:46:00" post-date="2015-12-03T16:46:42" arch="x86_64" filetype="Other">
                            <description>one file to rule them all</description>
                            <user format="username">toto</user>
                        </file>
                    </release>
                </package>
            </frs>
        </project>
XML;
        $xml_element = new SimpleXMLElement($xml);

        $user_id = 42;
        $this->user_finder->shouldReceive('getUser')->andReturns(new PFUser(array('user_id' => $user_id)));

        $package_id = 1337;
        $package_array_with_id = array(
            'package_id' => $package_id,
            'group_id'   => 123,
            'name'       => "package",
            'status_id'  => FRSPackage::STATUS_ACTIVE,
            'rank'       => 0,
            'approve_license' => true
        );

        $expected_package_array = $this->getDefaultPackage('package');
        $this->package_dao->shouldReceive('createFromArray')->with($expected_package_array)->once()->andReturns($package_id);
        $this->package_dao->shouldReceive('searchById')->with($package_id, FRSPackageDao::INCLUDE_DELETED)->andReturns(\TestHelper::arrayToDar($package_array_with_id));

        $release_id = 8665;
        $expected_release_array = array(
            'release_id' => 0,
            'package_id' => $package_id,
            'name' => 'release',
            'notes' => 'some notes',
            'changes' => 'some changes',
            'status_id' => FRSRelease::STATUS_ACTIVE,
            'preformatted' => false,
            'release_date' => strtotime('2015-12-03T14:55:00'),
            'released_by' => $user_id);
        $this->release_dao->shouldReceive('createFromArray')->with($expected_release_array)->once()->andReturns($release_id);

        $release_array_with_group = $expected_release_array;
        $release_array_with_group['group_id'] = 123;

        $this->filetype_dao->shouldReceive('searchTypeId')->andReturns(667);
        $this->processor_dao->shouldReceive('searchProcessorId')->andReturns(69);
        $this->release_dao->shouldReceive('searchById')->andReturns(\TestHelper::arrayToDar($release_array_with_group));
        $this->file_dao->shouldReceive('searchFileByName')->andReturns(\TestHelper::emptyDar());

        $file_id = 12569;
        $expected_file_array = array(
            'file_id'       => null,
            'filename'      => "p1337_r8665/lefichier",
            'filepath'      => "p1337_r8665/lefichier_" . $_SERVER['REQUEST_TIME'],
            'release_id'    => $release_id,
            'type_id'       => 667,
            'processor_id'  => 69,
            'release_time'  => strtotime('2015-12-03T16:46:00'),
            'file_location' => $GLOBALS['ftp_frs_dir_prefix'] . "/test_project/p1337_r8665/lefichier_" . $_SERVER['REQUEST_TIME'],
            'file_size'     => 14,
            'post_date'     => strtotime('2015-12-03T16:46:42'),
            'status'        => "A",
            'computed_md5'  => "c58ef9ab0b1fc7f6f90ffb607dee0073",
            'reference_md5' => "c58ef9ab0b1fc7f6f90ffb607dee0073",
            'user_id'       => $user_id,
            'comment'       => "one file to rule them all");

        $this->file_dao->shouldReceive('createFromArray')->with($expected_file_array)->once()->andReturns($file_id);

        $expected_file_array_with_id = $expected_file_array;
        $expected_file_array_with_id['id'] = $file_id;
        $this->file_dao->shouldReceive('searchById')->with($file_id)->andReturns(\TestHelper::arrayToDar($expected_file_array_with_id));

        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('getGroupId')->andReturn(123);
        $this->release_factory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);

        $frs_mapping = array();
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, $extraction_path, $frs_mapping);
    }

    public function testItShouldImportReleaseWithLinks(): void
    {
        $extraction_path = $this->getTmpDir();
        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProjectFromDbRow(array('group_id' => 123, 'unix_group_name' => 'test_project'));
        $xml = <<<XML
        <project>
            <frs>
                <package name="package">
                    <read-access><ugroup>project_members</ugroup></read-access>
                    <release name="release" time="2015-12-03T14:55:00" preformatted="false">
                        <read-access><ugroup>project_members</ugroup></read-access>
                        <notes>some notes</notes>
                        <changes>some changes</changes>
                        <user format="username">toto</user>
                        <link
                          name="test"
                          url="http://example.com"
                          release-time="2016-07-19T10:38:19+01:00">
                          <user format="username">goupix</user>
                        </link>
                    </release>
                </package>
            </frs>
        </project>
XML;
        $xml_element = new SimpleXMLElement($xml);

        $user_id = 42;
        $this->user_finder->shouldReceive('getUser')->andReturns(new PFUser(array('user_id' => $user_id)));

        $package_id = 1337;
        $package_array_with_id = array(
            'package_id' => $package_id,
            'group_id'   => 123,
            'name'       => "package",
            'status_id'  => FRSPackage::STATUS_ACTIVE,
            'rank'       => 0,
            'approve_license' => true
        );

        $expected_package_array = $this->getDefaultPackage('package');
        $this->package_dao->shouldReceive('createFromArray')->with($expected_package_array)->once()->andReturns($package_id);
        $this->package_dao->shouldReceive('searchById')->with($package_id, FRSPackageDao::INCLUDE_DELETED)->andReturns(\TestHelper::arrayToDar($package_array_with_id));

        $release_id = 8665;
        $expected_release_array = array(
            'release_id' => 0,
            'package_id' => $package_id,
            'name' => 'release',
            'notes' => 'some notes',
            'changes' => 'some changes',
            'status_id' => FRSRelease::STATUS_ACTIVE,
            'preformatted' => false,
            'release_date' => strtotime('2015-12-03T14:55:00'),
            'released_by' => $user_id);
        $this->release_dao->shouldReceive('createFromArray')->with($expected_release_array)->once()->andReturns($release_id);

        $release_array_with_group = $expected_release_array;
        $release_array_with_group['group_id'] = 123;

        $this->link_dao->shouldReceive('create')->andReturns(true);

        $release = \Mockery::spy(FRSRelease::class);
        $release->shouldReceive('getGroupId')->andReturn(123);
        $this->release_factory->shouldReceive('getFRSReleaseFromDb')->andReturns($release);

        $frs_mapping = array();
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, $extraction_path, $frs_mapping);
    }

    private function getDefaultPackage($name)
    {
        return array(
            'package_id' => null,
            'group_id' => 123,
            'name' => $name,
            'status_id' => FRSPackage::STATUS_ACTIVE,
            'rank' => 'end',
            'approve_license' => '1');
    }
}
