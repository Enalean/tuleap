<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use ForgeConfig;
use Git;
use Git_LogDao;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\XML\ArchiveException;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\TemporaryTestDirectory;

class GitXMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var \Git_LogDao
     */
    private $git_log_dao;
    /**
     * @var GitXmlExporter
     */
    private $xml_exporter;

    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;

    /**
     * @var ZipArchive
     */
    private $zip;

    /**
     * @var \GitPermissionsManager
     */
    private $permission_manager;

    private $export_folder;

    /**
     * @var \EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->export_folder = $this->getTmpDir();

        if (! is_dir($this->getTmpDir() . '/export')) {
            mkdir($this->getTmpDir() . '/export');
        }
        touch($this->getTmpDir() . '/export/MyRepo.bundle');

        $GLOBALS['Language']->shouldReceive('getText')->andReturns('projects-admins');

        $this->permission_manager = \Mockery::spy(\GitPermissionsManager::class);
        $this->permission_manager->shouldReceive('getCurrentGitAdminUgroups')->andReturns(array(
            4,
            5
        ));

        $ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $ugroup         = \Mockery::spy(\ProjectUGroup::class);
        $ugroup->shouldReceive('getTranslatedName')->andReturns('custom');
        $ugroup_manager->shouldReceive('getUGroup')->andReturns($ugroup);

        $repository_factory = \Mockery::spy(\GitRepositoryFactory::class);
        $repository         = \Mockery::spy(\GitRepository::class);
        $repository->shouldReceive('getName')->andReturns('MyRepo');
        $repository->shouldReceive('getDescription')->andReturns('Repository description');
        $repository->shouldReceive('getFullPath')->andReturns($this->export_folder);
        $repository->shouldReceive('getParent')->andReturns(false);
        $repository->shouldReceive('isInitialized')->andReturns(true);

        $forked_repository = \Mockery::spy(\GitRepository::class);
        $forked_repository->shouldReceive('getName')->andReturns('MyForkedRepo');
        $forked_repository->shouldReceive('getDescription')->andReturns('Forked repository');
        $forked_repository->shouldReceive('getParent')->andReturns(true);

        $empty_repository = \Mockery::spy(\GitRepository::class);
        $empty_repository->shouldReceive('getName')->andReturns('Empty');
        $empty_repository->shouldReceive('getDescription')->andReturns('Empty repository');
        $empty_repository->shouldReceive('getFullPath')->andReturns($this->export_folder);
        $empty_repository->shouldReceive('getParent')->andReturns(false);
        $empty_repository->shouldReceive('isInitialized')->andReturns(false);

        $repository_factory->shouldReceive('getAllRepositories')->andReturns(array($repository, $forked_repository, $empty_repository));

        $this->user_manager  = \Mockery::spy(\UserManager::class);
        $this->event_manager = \Mockery::spy(\EventManager::class);
        $this->git_log_dao   = \Mockery::spy(Git_LogDao::class);

        $this->xml_exporter = new GitXmlExporter(
            \Mockery::spy(\Project::class),
            $this->permission_manager,
            $ugroup_manager,
            $repository_factory,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            \Mockery::spy(\Tuleap\GitBundle::class),
            $this->git_log_dao,
            $this->user_manager,
            new \UserXMLExporter(
                $this->user_manager,
                new \UserXMLExportedCollection(new \XML_RNGValidator(), new \XML_SimpleXMLCDATAFactory())
            ),
            $this->event_manager
        );

        $this->event_manager->shouldReceive('processEvent')->once();

        $data           = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';
        $this->xml_tree = new SimpleXMLElement($data);

        $this->zip  = new ZipArchive($this->export_folder . '/archive.zip');

        ForgeConfig::set('tmp_dir', $this->export_folder);
    }

    protected function tearDown(): void
    {
        try {
            $this->zip->close();
        } catch (ArchiveException $e) {
        }
        unlink($this->getTmpDir() . '/export/MyRepo.bundle');
        rmdir($this->getTmpDir() . '/export');

        parent::tearDown();
    }

    public function testItExportGitRepositories(): void
    {
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $this->assertCount(2, $this->xml_tree->git->repository);

        $exported_repositories = $this->xml_tree->git[0];
        $repository           = $exported_repositories->repository;
        $attrs                = $repository->attributes();

        $this->assertEquals('MyRepo', $attrs['name']);
        $this->assertEquals('Repository description', $attrs['description']);
        $this->assertEquals('export/MyRepo.bundle', $attrs['bundle-path']);

        $repository_02 = $exported_repositories->repository[1];
        $attrs_02      = $repository_02->attributes();

        $this->assertEquals('Empty', $attrs_02['name']);
        $this->assertEquals('Empty repository', $attrs_02['description']);
        $this->assertEquals('', $attrs_02['bundle-path']);
    }

    public function testItExportsUGroupsAdmins(): void
    {
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $ugroups_admin = $this->xml_tree->git->{'ugroups-admin'}->ugroup;

        $this->assertEquals('projects-admins', (string) $ugroups_admin[0]);
        $this->assertEquals('custom', (string) $ugroups_admin[1]);
    }

    public function testItExportRepositoryPermissions(): void
    {
        $this->permission_manager->shouldReceive('getRepositoryGlobalPermissions')->andReturns(array(
            Git::PERM_READ  => array(3, 5),
            Git::PERM_WRITE => array(3),
            Git::PERM_WPLUS => array(5)
        ));

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        $this->assertEquals('projects-admins', (string) $readers[0]);
        $this->assertEquals('custom', (string) $readers[1]);

        $writers = $this->xml_tree->git->repository->write->ugroup;
        $this->assertEquals('projects-admins', (string) $writers[0]);

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        $this->assertEquals('custom', (string) $wplus[0]);
    }

    public function testItDoesNotCreateWritePermissionIfRepositoryDontHaveCustomWritePermission(): void
    {
        $this->permission_manager->shouldReceive('getRepositoryGlobalPermissions')->andReturns(array(
            Git::PERM_READ  => array(3, 5),
            Git::PERM_WPLUS => array(5)
        ));

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        $this->assertEquals('projects-admins', (string) $readers[0]);
        $this->assertEquals('custom', (string) $readers[1]);

        $writers = $this->xml_tree->git->repository->write->ugroup;
        $this->assertEmpty((string) $writers);

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        $this->assertEquals('custom', (string) $wplus[0]);
    }

    public function testItDoesNotCreateWplusPermissionIfRepositoryDontHaveCustomWplusPermission(): void
    {
        $this->permission_manager->shouldReceive('getRepositoryGlobalPermissions')->andReturns(array(
            Git::PERM_READ  => array(3, 5),
            Git::PERM_WRITE => array(3)
        ));

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        $this->assertEquals('projects-admins', (string) $readers[0]);
        $this->assertEquals('custom', (string) $readers[1]);

        $writers = $this->xml_tree->git->repository->write->ugroup;
        $this->assertEquals('projects-admins', (string) $writers[0]);

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        $this->assertEmpty((string) $wplus);
    }

    public function testItExportGitLastPushDateData(): void
    {
        $this->git_log_dao->shouldReceive('getLastPushForRepository')->andReturns([
            'repository_id'  => 2,
            'user_id'        => 102,
            'push_date'      => 1527145976,
            'commits_number' => 1,
            'refname'        => "refs/heads/master",
            'operation_type' => "create",
            'refname_type'   => "branch"
        ]);

        $this->user_manager->shouldReceive('getUserById')->andReturns(
            new PFUser(['user_name' => 'my user name', 'language_id' => 'en'])
        );

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $this->assertCount(2, $this->xml_tree->git->repository);

        $exported_repository = $this->xml_tree->git->repository[0];
        $last_push_date      = $exported_repository->{'last-push-date'};
        $attrs               = $last_push_date->attributes();
        $this->assertEquals('my user name', (string) $last_push_date->user);
        $this->assertEquals('1527145976', (string) $attrs['push_date']);
    }
}
