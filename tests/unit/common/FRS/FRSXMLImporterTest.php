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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\UploadedLinksUpdater;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FRSPackageFactoryMock extends FRSPackageFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    // bypass it for the tests as it calls global functions which access to the db
    #[\Override]
    public function setDefaultPermissions(FRSPackage $package)
    {
    }

    #[\Override]
    protected function setLicenseAgreementAtPackageCreation(FRSPackage $package, ?int $original_approval_license)
    {
    }
}

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FRSXMLImporterTest_FRSFileFactory extends FRSFileFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function __construct()
    {
        parent::__construct();
        $this->setFileForge(['true']);
    }
}

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FRSXMLImporterTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,PSR1.Classes.ClassDeclaration.MultipleClasses
{
    use \Tuleap\TemporaryTestDirectory;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\ForgeConfigSandbox;

    protected \Tuleap\FRS\UploadedLinksDao&Stub $link_dao;
    protected FRSPermissionCreator&MockObject $frs_permission_creator;
    private FRSPackageFactoryMock $package_factory;
    private FRSReleaseFactory&Stub $release_factory;
    private FRSXMLImporterTest_FRSFileFactory $file_factory;
    private FRSPackageDao&MockObject $package_dao;
    private PermissionsManager&Stub $permissions_manager;
    private FRSReleaseDao&MockObject $release_dao;
    private FRSFileDao&MockObject $file_dao;
    private FRSProcessorDao&Stub $processor_dao;
    private FRSFileTypeDao&Stub $filetype_dao;
    private \User\XML\Import\IFindUserFromXMLReference&Stub $user_finder;
    private UserManager&Stub $user_manager;
    private UGroupDao&Stub $ugroup_dao;
    private XMLImportHelper&Stub $xml_import_helper;
    private FRSXMLImporter $frs_importer;

    #[\Override]
    protected function setUp(): void
    {
        $this->package_factory = new FRSPackageFactoryMock();
        $this->release_factory = $this->getStubBuilder(FRSReleaseFactory::class)
            ->onlyMethods(['getFRSReleaseFromDb'])
            ->getStub();
        $this->file_factory    = new FRSXMLImporterTest_FRSFileFactory();

        $this->package_dao          = $this->createMock(FRSPackageDao::class);
        $this->package_factory->dao = $this->package_dao;
        FRSPackageFactory::setInstance($this->package_factory);

        $this->permissions_manager = $this->createStub(PermissionsManager::class);
        PermissionsManager::setInstance($this->permissions_manager);

        $this->release_dao          = $this->createMock(FRSReleaseDao::class);
        $this->release_factory->dao = $this->release_dao;
        FRSReleaseFactory::setInstance($this->release_factory);

        $this->file_dao                      = $this->createMock(FRSFileDao::class);
        $this->file_factory->dao             = $this->file_dao;
        $this->file_factory->release_factory = $this->release_factory;

        $this->processor_dao = $this->createStub(FRSProcessorDao::class);
        $this->filetype_dao  = $this->createStub(FRSFileTypeDao::class);

        $this->user_finder  = $this->createStub(\User\XML\Import\IFindUserFromXMLReference::class);
        $this->user_manager = $this->createStub(UserManager::class);
        UserManager::setInstance($this->user_manager);

        $this->ugroup_dao = $this->createStub(UGroupDao::class);
        $this->ugroup_dao->method('searchByGroupIdAndName')->willReturn(null);

        $this->xml_import_helper      = $this->createStub(XMLImportHelper::class);
        $this->frs_permission_creator = $this->createMock(\Tuleap\FRS\FRSPermissionCreator::class);

        $this->link_dao = $this->createStub(\Tuleap\FRS\UploadedLinksDao::class);
        $frs_log        = $this->createStub(FRSLog::class);
        $frs_log->method('addLog');
        $links_updater = new UploadedLinksUpdater($this->link_dao, $frs_log);

        $logger = new \Psr\Log\NullLogger();

        $this->frs_importer = new FRSXMLImporter(
            $logger,
            $this->package_factory,
            $this->release_factory,
            $this->file_factory,
            $this->user_finder,
            new UGroupManager($this->ugroup_dao),
            $this->frs_permission_creator,
            $links_updater,
            $this->processor_dao,
            $this->filetype_dao
        );

        $em = $this->createStub(EventManager::class);
        $em->method('processEvent');
        $em->method('addListener');
        EventManager::setInstance($em);
        ForgeConfig::set('ftp_incoming_dir', $this->getTmpDir());
        ForgeConfig::set('ftp_frs_dir_prefix', $this->getTmpDir());
    }

    #[\Override]
    protected function tearDown(): void
    {
        FRSPackageFactory::clearInstance();
        ProjectManager::clearInstance();
        FRSReleaseFactory::clearInstance();
        PermissionsManager::clearInstance();
        UserManager::clearInstance();
        EventManager::clearInstance();
        parent::tearDown();
    }

    public function testItShouldImportOnePackageWithDefaultValues()
    {
        $pm                     = ProjectManager::instance();
        $project                = $pm->getProjectFromDbRow(['group_id' => 123, 'unix_group_name' => 'test_project']);
        $xml                    = <<<XML
        <project>
            <frs>
                <package name="empty_package">
                    <read-access><ugroup>project_members</ugroup></read-access>
                </package>
            </frs>
        </project>
XML;
        $xml_element            = new SimpleXMLElement($xml);
        $expected_package_array = $this->getDefaultPackage('empty_package');
        $this->package_dao->expects($this->once())->method('createFromArray')->with($expected_package_array);

        $this->permissions_manager->method('savePermissions');
        $this->frs_permission_creator->expects($this->never())->method('savePermissions');

        $this->release_dao->expects($this->never())->method('createFromArray');
        $this->file_dao->expects($this->never())->method('createFromArray');

        $frs_mapping = [];
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);
    }

    public function testItShouldImportPermissions()
    {
        $pm      = ProjectManager::instance();
        $project = $pm->getProjectFromDbRow(['group_id' => 123, 'unix_group_name' => 'test_project']);
        $matcher = self::atLeast(2);

        $this->frs_permission_creator->expects($matcher)->method('savePermissions')->willReturnCallback(function (...$parameters) use ($matcher, $project) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($project, $parameters[0]);
                self::assertSame([2], $parameters[1]);
                self::assertSame(FRSPermission::FRS_READER, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($project, $parameters[0]);
                self::assertSame([3], $parameters[1]);
                self::assertSame(FRSPermission::FRS_ADMIN, $parameters[2]);
            }
        });

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

        $this->package_dao->expects($this->never())->method('createFromArray');
        $this->release_dao->expects($this->never())->method('createFromArray');
        $this->file_dao->expects($this->never())->method('createFromArray');

        $xml_element = new SimpleXMLElement($xml);
        $frs_mapping = [];
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);
    }

    public function testItShouldImportOnePackageWithOneRelease()
    {
        $pm          = ProjectManager::instance();
        $project     = $pm->getProjectFromDbRow(['group_id' => 123, 'unix_group_name' => 'test_project']);
        $xml         = <<<XML
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
        $this->user_finder->method('getUser')->willReturn(new PFUser(['user_id' => $user_id]));

        $expected_package_array = $this->getDefaultPackage('package');
        $package_id             = 1337;
        $this->package_dao->expects($this->once())->method('createFromArray')->with($expected_package_array)->willReturn($package_id);

        $expected_release_array = [
            'release_id' => 0,
            'package_id' => $package_id,
            'name' => 'release',
            'notes' => 'some notes',
            'changes' => 'some changes',
            'status_id' => FRSRelease::STATUS_ACTIVE,
            'preformatted' => false,
            'release_date' => strtotime('2015-12-03T14:55:00'),
            'released_by' => $user_id,
        ];
        $this->release_dao->expects($this->once())->method('createFromArray')->with($expected_release_array);

        $this->permissions_manager->method('savePermissions');
        $this->frs_permission_creator->expects($this->never())->method('savePermissions');
        $this->file_dao->expects($this->never())->method('createFromArray');


        $frs_mapping = [];
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);
    }

    public function testItShouldImportOnePackageWithOneReleaseLinkedToAnArtifact()
    {
        $pm      = ProjectManager::instance();
        $project = $pm->getProjectFromDbRow(['group_id' => 123, 'unix_group_name' => 'test_project']);
        $xml     = <<<XML
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
        $release    = $this->createStub(FRSRelease::class);

        $release->method('getGroupId')->willReturn(123);
        $this->user_finder->method('getUser')->willReturn(new PFUser(['user_id' => $user_id]));
        $this->package_dao->expects($this->once())->method('createFromArray')->willReturn($package_id);
        $this->release_dao->expects($this->once())->method('createFromArray')->willReturn(47);
        $this->release_factory->method('getFRSReleaseFromDb')->willReturn($release);

        $this->permissions_manager->method('savePermissions');
        $this->frs_permission_creator->expects($this->never())->method('savePermissions');
        $this->file_dao->expects($this->never())->method('createFromArray');

        $frs_mapping = [];
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, '', $frs_mapping);

        self::assertSame($frs_mapping[47], 'A101');
    }

    public function testItShouldImportOnePackageWithOneReleaseWithOneFile(): void
    {
        $extraction_path = $this->getTmpDir();
        $temp_file       = tempnam($extraction_path, 'thefile_');
        fwrite(fopen($temp_file, 'w+'), 'such file, wow');
        $file_name = basename($temp_file);

        $pm          = ProjectManager::instance();
        $project     = $pm->getProjectFromDbRow(['group_id' => 123, 'unix_group_name' => 'test_project']);
        $xml         = <<<XML
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
        $this->user_finder->method('getUser')->willReturn(new PFUser(['user_id' => $user_id]));

        $package_id            = 1337;
        $package_array_with_id = [
            'package_id' => $package_id,
            'group_id' => 123,
            'name' => 'package',
            'status_id' => FRSPackage::STATUS_ACTIVE,
            'rank' => 0,
            'approve_license' => true,
        ];

        $expected_package_array = $this->getDefaultPackage('package');
        $this->package_dao->expects($this->once())->method('createFromArray')->with($expected_package_array)->willReturn($package_id);
        $this->package_dao->method('searchById')->with($package_id, FRSPackageDao::INCLUDE_DELETED)->willReturn(TestHelper::arrayToDar($package_array_with_id));

        $release_id             = 8665;
        $expected_release_array = [
            'release_id' => 0,
            'package_id' => $package_id,
            'name' => 'release',
            'notes' => 'some notes',
            'changes' => 'some changes',
            'status_id' => FRSRelease::STATUS_ACTIVE,
            'preformatted' => false,
            'release_date' => strtotime('2015-12-03T14:55:00'),
            'released_by' => $user_id,
        ];
        $this->release_dao->expects($this->once())->method('createFromArray')->with($expected_release_array)->willReturn($release_id);

        $release_array_with_group             = $expected_release_array;
        $release_array_with_group['group_id'] = 123;

        $this->filetype_dao->method('searchTypeId')->willReturn(667);
        $this->processor_dao->method('searchProcessorId')->willReturn(69);
        $this->release_dao->method('searchById')->willReturn(TestHelper::arrayToDar($release_array_with_group));
        $this->file_dao->method('searchFileByName')->willReturn(TestHelper::emptyDar());

        $file_id             = 12569;
        $expected_file_array = [
            'file_id' => null,
            'filename' => 'p1337_r8665/lefichier',
            'filepath' => 'p1337_r8665/lefichier_' . $_SERVER['REQUEST_TIME'],
            'release_id' => $release_id,
            'type_id' => 667,
            'processor_id' => 69,
            'release_time' => strtotime('2015-12-03T16:46:00'),
            'file_location' => ForgeConfig::get('ftp_frs_dir_prefix') . '/test_project/p1337_r8665/lefichier_' . $_SERVER['REQUEST_TIME'],
            'file_size' => 14,
            'post_date' => strtotime('2015-12-03T16:46:42'),
            'status' => 'A',
            'computed_md5' => 'c58ef9ab0b1fc7f6f90ffb607dee0073',
            'reference_md5' => 'c58ef9ab0b1fc7f6f90ffb607dee0073',
            'user_id' => $user_id,
            'comment' => 'one file to rule them all',
        ];

        $this->file_dao->expects($this->once())->method('createFromArray')->with($expected_file_array)->willReturn($file_id);

        $expected_file_array_with_id       = $expected_file_array;
        $expected_file_array_with_id['id'] = $file_id;
        $this->file_dao->method('searchById')->with($file_id)->willReturn(TestHelper::arrayToDar($expected_file_array_with_id));

        $release = $this->createStub(FRSRelease::class);
        $release->method('getGroupId')->willReturn(123);
        $release->method('getPackageID');
        $release->method('getReleaseID');
        $this->release_factory->method('getFRSReleaseFromDb')->willReturn($release);

        $this->permissions_manager->method('savePermissions');
        $this->file_dao->method('isMarkedToBeRestored');
        $this->user_manager->method('getCurrentUser');
        $this->frs_permission_creator->expects($this->never())->method('savePermissions');

        $frs_mapping = [];
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, $extraction_path, $frs_mapping);
    }

    public function testItShouldImportReleaseWithLinks(): void
    {
        $extraction_path = $this->getTmpDir();
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProjectFromDbRow(['group_id' => 123, 'unix_group_name' => 'test_project']);
        $xml             = <<<XML
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
        $xml_element     = new SimpleXMLElement($xml);

        $user_id = 42;
        $this->user_finder->method('getUser')->willReturn(new PFUser(['user_id' => $user_id]));

        $package_id            = 1337;
        $package_array_with_id = [
            'package_id' => $package_id,
            'group_id' => 123,
            'name' => 'package',
            'status_id' => FRSPackage::STATUS_ACTIVE,
            'rank' => 0,
            'approve_license' => true,
        ];

        $expected_package_array = $this->getDefaultPackage('package');
        $this->package_dao->expects($this->once())->method('createFromArray')->with($expected_package_array)->willReturn($package_id);
        $this->package_dao->method('searchById')->with($package_id, FRSPackageDao::INCLUDE_DELETED)->willReturn(TestHelper::arrayToDar($package_array_with_id));

        $release_id             = 8665;
        $expected_release_array = [
            'release_id' => 0,
            'package_id' => $package_id,
            'name' => 'release',
            'notes' => 'some notes',
            'changes' => 'some changes',
            'status_id' => FRSRelease::STATUS_ACTIVE,
            'preformatted' => false,
            'release_date' => strtotime('2015-12-03T14:55:00'),
            'released_by' => $user_id,
        ];
        $this->release_dao->expects($this->once())->method('createFromArray')->with($expected_release_array)->willReturn($release_id);

        $release_array_with_group             = $expected_release_array;
        $release_array_with_group['group_id'] = 123;

        $this->link_dao->method('create')->willReturn(true);

        $release = $this->createStub(FRSRelease::class);
        $release->method('getGroupId')->willReturn(123);
        $this->release_factory->method('getFRSReleaseFromDb')->willReturn($release);

        $this->permissions_manager->method('savePermissions');

        $this->frs_permission_creator->expects($this->never())->method('savePermissions');
        $this->file_dao->expects($this->never())->method('createFromArray');

        $frs_mapping = [];
        $this->frs_importer->import(new Tuleap\Project\XML\Import\ImportConfig(), $project, $xml_element, $extraction_path, $frs_mapping);
    }

    private function getDefaultPackage($name)
    {
        return [
            'package_id' => null,
            'group_id' => 123,
            'name' => $name,
            'status_id' => FRSPackage::STATUS_ACTIVE,
            'rank' => 'end',
            'approve_license' => '1',
        ];
    }
}
