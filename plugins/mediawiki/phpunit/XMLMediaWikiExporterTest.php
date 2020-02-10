<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\MediaWiki;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use ForgeConfig;
use MediawikiLanguageManager;
use MediawikiManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Project\XML\Export\ZipArchive;
use UGroupManager;

class XMLMediaWikiExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MediawikiMaintenanceWrapper
     */
    private $maintenance_wrapper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var MediawikiLanguageManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $language_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MediawikiDataDir
     */
    private $mediawiki_data_dir;

    /**
     * @var MediawikiManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $mediawiki_manager;
    /**
     * @var XMLMediaWikiExporter
     */
    private $exporter;

    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;

    private $zip;
    private $fixtures_dir;
    /**
     * @var string
     */
    private $file;


    protected function setUp(): void
    {
        parent::setUp();

        $this->mediawiki_manager = Mockery::mock(MediawikiManager::class);

        $this->project = Mockery::mock(Project::class);

        $ugroup_manager = Mockery::mock(UGroupManager::class);
        $custom_ugroup  = Mockery::mock(ProjectUGroup::class);
        $custom_ugroup->shouldReceive('getName')->andReturn('custom');
        $custom_ugroup->shouldReceive('getId');
        $ugroup_manager->shouldReceive('getUGroup')->withArgs([$this->project, 5])->andReturn($custom_ugroup);

        $project_admins_ugroups = Mockery::mock(ProjectUGroup::class);
        $project_admins_ugroups->shouldReceive('getName')->andReturn('project-admins');
        $project_admins_ugroups->shouldReceive('getId');
        $ugroup_manager->shouldReceive('getUGroup')->withArgs([$this->project, 4])->andReturn($project_admins_ugroups);

        $this->language_manager = Mockery::mock(MediawikiLanguageManager::class);
        $this->language_manager->shouldReceive('getUsedLanguageForProject')->andReturn('fr_FR');

        $this->maintenance_wrapper = Mockery::mock(MediawikiMaintenanceWrapper::class);

        $this->mediawiki_data_dir = Mockery::mock(MediawikiDataDir::class);
        $this->logger                   = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->exporter           = new XMLMediaWikiExporter(
            $this->project,
            $this->mediawiki_manager,
            $ugroup_manager,
            $this->logger,
            $this->maintenance_wrapper,
            $this->language_manager,
            $this->mediawiki_data_dir
        );

        $data           = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';
        $this->xml_tree = new SimpleXMLElement($data);

        $structure = [
            'files' => []
        ];

        $base_dir = vfsStream::setup();
        $this->fixtures_dir = vfsStream::create($structure, $base_dir);
        chmod($this->fixtures_dir->getChild('files')->url(), '0777');

        $this->file = 'export_mw_101.xml';

        $this->zip = Mockery::mock(ZipArchive::class);

        ForgeConfig::store();
        ForgeConfig::set('codendi_cache_dir', $this->fixtures_dir->url());
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function testItDoesNotExportMediwikiWhenItNEverHaveBeenInstantiated(): void
    {
        $this->mediawiki_data_dir->shouldReceive('getMediawikiDir')->once()->andReturn('incorrectdir');
        $this->logger->shouldReceive('info')->once();
        $this->language_manager->shouldReceive('getUsedLanguageForProject')->never();

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir
        );
    }

    public function testItExportsMediaWikiAttributes(): void
    {
        $this->mediawiki_manager->shouldReceive('getReadAccessControl')->andReturn([]);
        $this->mediawiki_manager->shouldReceive('getWriteAccessControl')->andReturn([]);

        $this->mediawiki_data_dir->shouldReceive('getMediawikiDir')->once()->andReturn(vfsStream::setup()->url());
        $this->logger->shouldReceive('info');

        $this->maintenance_wrapper->shouldReceive('dumpBackupFull')->once();
        $this->maintenance_wrapper->shouldReceive('dumpUploads')->once();

        $this->zip->shouldReceive('addFile')->once();

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir->url()
        );

        $mediawiki = $this->xml_tree->mediawiki;
        $attrs     = $mediawiki->attributes();

        $this->assertEquals($attrs['pages-backup'], 'wiki_pages.xml');
        $this->assertEquals($attrs['language'], 'fr_FR');
        $this->assertEquals($attrs['files-folder-backup'], 'files');
    }

    public function testItExportsMediaWikiPermissions() : void
    {
        $this->mediawiki_manager->shouldReceive('getReadAccessControl')->andReturn(
            [
                4,
                5
            ]
        );
        $this->mediawiki_manager->shouldReceive('getWriteAccessControl')->andReturn(
            [
                5
            ]
        );

        $this->mediawiki_data_dir->shouldReceive('getMediawikiDir')->once()->andReturn(vfsStream::setup()->url());
        $this->logger->shouldReceive('info');

        $this->maintenance_wrapper->shouldReceive('dumpBackupFull')->once();
        $this->maintenance_wrapper->shouldReceive('dumpUploads')->once();

        $this->zip->shouldReceive('addFile')->once();

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir->url()
        );

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEquals((string) $readers[0], 'project-admins');
        $this->assertEquals((string) $readers[1], 'custom');

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertEquals((string) $writers[0], 'custom');
    }

    public function testItDoesNotExportReadPermissionsIfNoReadersAreDefined(): void
    {
        $this->mediawiki_manager->shouldReceive('getReadAccessControl')->andReturn(
            null
        );
        $this->mediawiki_manager->shouldReceive('getWriteAccessControl')->andReturn(
            [
                5
            ]
        );

        $this->mediawiki_data_dir->shouldReceive('getMediawikiDir')->once()->andReturn(vfsStream::setup()->url());
        $this->logger->shouldReceive('info');

        $this->maintenance_wrapper->shouldReceive('dumpBackupFull')->once();
        $this->maintenance_wrapper->shouldReceive('dumpUploads')->once();

        $this->zip->shouldReceive('addFile')->once();

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir->url()
        );

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertFalse(isset($readers[0]));

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertEquals((string) $writers[0], 'custom');
    }

    public function testItDoesNotExportWritePermissionsIfNoWritersAreDefined(): void
    {
        $this->mediawiki_manager->shouldReceive('getReadAccessControl')->andReturn(
            [
                4,
                5
            ]
        );
        $this->mediawiki_manager->shouldReceive('getWriteAccessControl')->andReturn(
            null
        );

        $this->mediawiki_data_dir->shouldReceive('getMediawikiDir')->once()->andReturn(vfsStream::setup()->url());
        $this->logger->shouldReceive('info');

        $this->maintenance_wrapper->shouldReceive('dumpBackupFull')->once();
        $this->maintenance_wrapper->shouldReceive('dumpUploads')->once();

        $this->zip->shouldReceive('addFile')->once();

        $this->exporter->exportToXml(
            $this->xml_tree,
            $this->zip,
            $this->file,
            $this->fixtures_dir->url()
        );

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEquals((string) $readers[0], 'project-admins');
        $this->assertEquals((string) $readers[1], 'custom');

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertFalse(isset($writers[0]));
    }
}
