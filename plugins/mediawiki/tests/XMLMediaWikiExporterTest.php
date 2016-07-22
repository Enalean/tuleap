<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\MediaWiki;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

require_once dirname(__FILE__) . '/../include/MediawikiManager.class.php';

use ForgeConfig;
use SimpleXMLElement;
use Tuleap\Project\XML\ArchiveException;
use Tuleap\Project\XML\Export\ZipArchive;
use TuleapTestCase;

class XMLMediaWikiExporterTest extends TuleapTestCase
{

    /**
     * @var XMLMediaWikiExporter
     */
    private $exporter;

    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;

    /**
     * @var ZipArchive
     */
    private $zip;

    /**
     * @var \MediawikiManager
     */
    private $mediawiki_manager;

    private $mediawiki_data_dir;
    private $fixtures_dir;
    private $file;

    public function setUp()
    {
        parent::setUp();

        $GLOBALS['Language'] = mock('BaseLanguage');
        stub($GLOBALS['Language'])->getText()->returns('projects-admins');

        $this->mediawiki_manager = mock('MediawikiManager');

        $project = mock('Project');

        $ugroup_manager = mock('UGroupManager');
        $custom_ugroup  = mock('ProjectUGroup');
        stub($custom_ugroup)->getName()->returns('custom');
        stub($ugroup_manager)->getUGroup($project, 5)->returns($custom_ugroup);

        $project_admins_ugroups = mock('ProjectUGroup');
        stub($project_admins_ugroups)->getName()->returns('project-admins');
        stub($ugroup_manager)->getUGroup($project, 4)->returns($project_admins_ugroups);

        $system_command = mock('System_Command');

        $language_manager = mock('MediawikiLanguageManager');
        stub($language_manager)->getUsedLanguageForProject()->returns('fr_FR');

        $this->exporter = new XMLMediaWikiExporter(
            $system_command,
            $project,
            $this->mediawiki_manager,
            $ugroup_manager,
            mock('ProjectXMLExporterLogger'),
            new MediawikiMaintenanceWrapper($system_command),
            $language_manager
        );

        $data           = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';
        $this->xml_tree = new SimpleXMLElement($data);

        $this->fixtures_dir       = $this->getTmpDir();
        $this->mediawiki_data_dir = $this->getTmpDir() . '/files';
        if (! is_dir($this->mediawiki_data_dir)) {
            mkdir($this->mediawiki_data_dir);
        }

        $this->file = 'export_mw_101.xml';
        touch($this->fixtures_dir . "/" . $this->file);

        $this->zip  = new ZipArchive($this->fixtures_dir . '/export.zip');

        ForgeConfig::store();
        ForgeConfig::set('codendi_cache_dir', $this->fixtures_dir);
    }

    public function tearDown()
    {
        ForgeConfig::restore();

        try {
            $this->zip->close();
        } catch (ArchiveException $e) {
        }
        unlink($this->getTmpDir() . "/" . $this->file);
        unlink($this->getTmpDir() . '/export.zip');

        parent::tearDown();
    }

    public function itExportsMediaWikiAttributes()
    {
        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir,
            $this->mediawiki_data_dir
        );

        $mediawiki = $this->xml_tree->mediawiki;
        $attrs     = $mediawiki->attributes();

        $this->assertEqual($attrs['pages-backup'], 'wiki_pages.xml');
        $this->assertEqual($attrs['language'], 'fr_FR');
        $this->assertEqual($attrs['files-folder-backup'], 'files');
    }

    public function itExportsMediaWikiPermissions()
    {
        stub($this->mediawiki_manager)->getReadAccessControl()->returns(
            array(
                4,
                5
            )
        );
        stub($this->mediawiki_manager)->getWriteAccessControl()->returns(
            array(
                5
            )
        );

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir,
            $this->mediawiki_data_dir
        );

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEqual((string) $readers[0], 'project-admins');
        $this->assertEqual((string) $readers[1], 'custom');

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertEqual((string) $writers[0], 'custom');
    }

    public function itDoesNotExportReadPermissionsIfNoReadersAreDefined()
    {
        stub($this->mediawiki_manager)->getReadAccessControl()->returns(
            null
        );
        stub($this->mediawiki_manager)->getWriteAccessControl()->returns(
            array(
                5
            )
        );

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir,
            $this->mediawiki_data_dir
        );

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEqual((string) $readers[0], null);

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertEqual((string) $writers[0], 'custom');
    }

    public function itDoesNotExportWritePermissionsIfNoWritersAreDefined()
    {
        stub($this->mediawiki_manager)->getReadAccessControl()->returns(
            array(
                4,
                5
            )
        );
        stub($this->mediawiki_manager)->getWriteAccessControl()->returns(
            null
        );

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir,
            $this->mediawiki_data_dir
        );

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEqual((string) $readers[0], 'project-admins');
        $this->assertEqual((string) $readers[1], 'custom');

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertEqual((string) $writers[0], null);
    }
}
