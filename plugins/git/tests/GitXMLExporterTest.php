<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
use SimpleXMLElement;
use Tuleap\Project\XML\ArchiveException;
use Tuleap\Project\XML\Export\ZipArchive;
use TuleapTestCase;

class GitXMLExporterTest extends TuleapTestCase
{
    /**
     * @var GitXmlExporter
     */
    private $xml_exporter;

    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var ZipArchive
     */
    private $zip;

    /**
     * @var \GitPermissionsManager
     */
    private $permission_manager;

    private $export_folder;

    public function setUp()
    {
        parent::setUp();

        $this->export_folder = $this->getTmpDir();

        if (! is_dir($this->getTmpDir() . '/export')) {
            mkdir($this->getTmpDir() . '/export');
        }
        touch($this->getTmpDir() . '/export/MyRepo.bundle');

        $GLOBALS['Language'] = mock('BaseLanguage');
        stub($GLOBALS['Language'])->getText()->returns('projects-admins');

        $this->permission_manager = mock('GitPermissionsManager');
        stub($this->permission_manager)->getCurrentGitAdminUgroups()->returns(
            array(
                4,
                5
            )
        );

        $ugroup_manager = mock('UGroupManager');
        $ugroup         = mock('ProjectUGroup');
        stub($ugroup)->getTranslatedName()->returns('custom');
        stub($ugroup_manager)->getUGroup()->returns($ugroup);

        $repository_factory = mock('GitRepositoryFactory');
        $repository         = mock('GitRepository');
        stub($repository)->getName()->returns('MyRepo');
        stub($repository)->getDescription()->returns('Repository description');
        stub($repository)->getFullPath()->returns($this->export_folder);
        stub($repository)->getParent()->returns(false);
        stub($repository)->isInitialized()->returns(true);

        $forked_repository = mock('GitRepository');
        stub($forked_repository)->getName()->returns('MyForkedRepo');
        stub($forked_repository)->getDescription()->returns('Forked repository');
        stub($forked_repository)->getParent()->returns(true);

        $empty_repository = mock('GitRepository');
        stub($empty_repository)->getName()->returns('Empty');
        stub($empty_repository)->getDescription()->returns('Empty repository');
        stub($empty_repository)->getFullPath()->returns($this->export_folder);
        stub($empty_repository)->getParent()->returns(false);
        stub($empty_repository)->isInitialized()->returns(false);

        stub($repository_factory)->getAllRepositories()->returns(
            array($repository, $forked_repository, $empty_repository)
        );

        $this->xml_exporter = new GitXmlExporter(
            mock('Project'),
            $this->permission_manager,
            $ugroup_manager,
            $repository_factory,
            mock('Logger'),
            mock('System_Command'),
            mock('Tuleap\GitBundle')
        );

        $data           = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';
        $this->xml_tree = new SimpleXMLElement($data);

        $this->zip  = new ZipArchive($this->export_folder . '/archive.zip');
        $this->user = mock('PFUser');

        ForgeConfig::store();
        ForgeConfig::set('tmp_dir', $this->export_folder);
    }

    public function tearDown()
    {
        ForgeConfig::restore();

        try {
            $this->zip->close();
        } catch (ArchiveException $e) {
        }
        unlink($this->getTmpDir() . '/export/MyRepo.bundle');
        rmdir($this->getTmpDir() . '/export');

        parent::tearDown();
    }

    public function itExportGitRepositories()
    {
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $this->assertEqual(count($this->xml_tree->git->repository), 2);

        $exported_repository = $this->xml_tree->git[0];
        $repository          = $exported_repository->repository;
        $attrs               = $repository->attributes();

        $this->assertEqual($attrs['name'], 'MyRepo');
        $this->assertEqual($attrs['description'], 'Repository description');
        $this->assertEqual($attrs['bundle-path'], 'export/MyRepo.bundle');

        $exported_repository = $this->xml_tree->git[0];
        $repository          = $exported_repository->repository;
        $attrs               = $repository->attributes();

        $this->assertEqual($attrs['name'], 'MyRepo');
        $this->assertEqual($attrs['description'], 'Repository description');
        $this->assertEqual($attrs['bundle-path'], 'export/MyRepo.bundle');
    }

    public function itExportsUGroupsAdmins()
    {
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $ugroups_admin = $this->xml_tree->git->{'ugroups-admin'}->ugroup;
        $this->assertEqual((string) $ugroups_admin[0], 'projects-admins');
        $this->assertEqual((string) $ugroups_admin[1], 'custom');
    }

    public function itExportRepositoryPermissions()
    {
        stub($this->permission_manager)->getRepositoryGlobalPermissions()->returns(
            array(
                Git::PERM_READ  => array(3, 5),
                Git::PERM_WRITE => array(3),
                Git::PERM_WPLUS => array(5)
            )
        );

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        $this->assertEqual((string) $readers[0], 'projects-admins');
        $this->assertEqual((string) $readers[1], 'custom');

        $writers = $this->xml_tree->git->repository->write->ugroup;
        $this->assertEqual((string) $writers[0], 'projects-admins');

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        $this->assertEqual((string) $wplus[0], 'custom');
    }

    public function itDoesNotCreateWritePermissionIfRepositoryDontHaveCustomWritePermission()
    {
        stub($this->permission_manager)->getRepositoryGlobalPermissions()->returns(
            array(
                Git::PERM_READ  => array(3, 5),
                Git::PERM_WPLUS => array(5)
            )
        );

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        $this->assertEqual((string) $readers[0], 'projects-admins');
        $this->assertEqual((string) $readers[1], 'custom');

        $writers = $this->xml_tree->git->repository->write->ugroup;
        $this->assertEqual((string) $writers, null);

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        $this->assertEqual((string) $wplus[0], 'custom');
    }

    public function itDoesNotCreateWplusPermissionIfRepositoryDontHaveCustomWplusPermission()
    {
        stub($this->permission_manager)->getRepositoryGlobalPermissions()->returns(
            array(
                Git::PERM_READ  => array(3, 5),
                Git::PERM_WRITE => array(3)
            )
        );

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        $this->assertEqual((string) $readers[0], 'projects-admins');
        $this->assertEqual((string) $readers[1], 'custom');

        $writers = $this->xml_tree->git->repository->write->ugroup;
        $this->assertEqual((string) $writers[0], 'projects-admins');

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        $this->assertEqual((string) $wplus, null);
    }
}
