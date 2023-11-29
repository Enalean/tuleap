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

namespace Tuleap\Mediawiki;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'bootstrap.php';

use ForgeConfig;
use MediawikiLanguageManager;
use MediawikiManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Mediawiki\Tests\Stub\CheckXMLMediawikiExportabilityStub;
use Tuleap\Mediawiki\XML\CheckXMLMediawikiExportability;
use Tuleap\Project\XML\Export\ExportOptions;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use UGroupManager;
use UserXMLExporter;

class XMLMediaWikiExporterTest extends \Tuleap\Test\PHPUnit\TestCase
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
     * @var SimpleXMLElement
     */
    private $xml_tree;

    private $zip;
    private $fixtures_dir;


    protected function setUp(): void
    {
        parent::setUp();

        $this->mediawiki_manager = Mockery::mock(MediawikiManager::class);
        $this->project           = ProjectTestBuilder::aProject()->build();
        $this->language_manager  = Mockery::mock(MediawikiLanguageManager::class);
        $this->language_manager->shouldReceive('getUsedLanguageForProject')->andReturn('fr_FR');
        $this->maintenance_wrapper = Mockery::mock(MediawikiMaintenanceWrapper::class);
        $this->mediawiki_data_dir  = Mockery::mock(MediawikiDataDir::class);
        $this->logger              = Mockery::mock(\Psr\Log\LoggerInterface::class);

        $data           = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';
        $this->xml_tree = new SimpleXMLElement($data);

        $structure = [
            'files' => [],
        ];

        $base_dir           = vfsStream::setup();
        $this->fixtures_dir = vfsStream::create($structure, $base_dir);
        chmod($this->fixtures_dir->getChild('files')->url(), '0777');

        $this->zip = Mockery::mock(ZipArchive::class);

        ForgeConfig::store();
        ForgeConfig::set('codendi_cache_dir', $this->fixtures_dir->url());
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function testItDoesNotExportMediawikiWhenItCannotBeExported(): void
    {
        $this->logger->shouldReceive('info')->once();
        $this->language_manager->shouldReceive('getUsedLanguageForProject')->never();

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withoutExportableMediawiki());
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

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

        $mediawiki = $this->xml_tree->mediawiki;
        $attrs     = $mediawiki->attributes();

        $this->assertEquals($attrs['pages-backup'], 'wiki_pages.xml');
        $this->assertEquals($attrs['language'], 'fr_FR');
        $this->assertEquals($attrs['files-folder-backup'], 'files');
    }

    public function testItExportsMediaWikiPermissions(): void
    {
        $this->mediawiki_manager->shouldReceive('getReadAccessControl')->andReturn(
            [
                4,
                5,
            ]
        );
        $this->mediawiki_manager->shouldReceive('getWriteAccessControl')->andReturn(
            [
                5,
            ]
        );

        $this->mediawiki_data_dir->shouldReceive('getMediawikiDir')->once()->andReturn(vfsStream::setup()->url());
        $this->logger->shouldReceive('info');

        $this->maintenance_wrapper->shouldReceive('dumpBackupFull')->once();
        $this->maintenance_wrapper->shouldReceive('dumpUploads')->once();

        $this->zip->shouldReceive('addFile')->once();

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

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
                5,
            ]
        );

        $this->mediawiki_data_dir->shouldReceive('getMediawikiDir')->once()->andReturn(vfsStream::setup()->url());
        $this->logger->shouldReceive('info');

        $this->maintenance_wrapper->shouldReceive('dumpBackupFull')->once();
        $this->maintenance_wrapper->shouldReceive('dumpUploads')->once();

        $this->zip->shouldReceive('addFile')->once();

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

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
                5,
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

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEquals((string) $readers[0], 'project-admins');
        $this->assertEquals((string) $readers[1], 'custom');

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertFalse(isset($writers[0]));
    }

    private function exportToXML(CheckXMLMediawikiExportability $check_xml_mediawiki_exportability): void
    {
        $ugroup_manager = Mockery::mock(UGroupManager::class);
        $custom_ugroup  = Mockery::mock(ProjectUGroup::class);
        $custom_ugroup->shouldReceive('getName')->andReturn('custom');
        $custom_ugroup->shouldReceive('getId');

        $project_admins_ugroups = Mockery::mock(ProjectUGroup::class);
        $project_admins_ugroups->shouldReceive('getName')->andReturn('project-admins');
        $project_admins_ugroups->shouldReceive('getId');

        $ugroup_manager->shouldReceive('getUGroup')->withArgs([$this->project, 5])->andReturn($custom_ugroup);
        $ugroup_manager->shouldReceive('getUGroup')->withArgs([$this->project, 4])->andReturn($project_admins_ugroups);

        $exporter = new XMLMediaWikiExporter(
            $this->mediawiki_manager,
            $ugroup_manager,
            $this->logger,
            $this->maintenance_wrapper,
            $this->language_manager,
            $this->mediawiki_data_dir,
            $check_xml_mediawiki_exportability
        );

        $exporter->exportToXml($this->getExportXMLProjectEvent());
    }

    private function getExportXMLProjectEvent(): ExportXmlProject
    {
        return new ExportXmlProject(
            $this->project,
            new ExportOptions(ExportOptions::MODE_ALL, false, []),
            $this->xml_tree,
            UserTestBuilder::anActiveUser()->build(),
            $this->createStub(UserXMLExporter::class),
            $this->zip,
            $this->fixtures_dir->url(),
            $this->logger
        );
    }
}
