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

use ColinODell\PsrTestLogger\TestLogger;
use ForgeConfig;
use MediawikiLanguageManager;
use MediawikiManager;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLMediaWikiExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MediawikiMaintenanceWrapper&MockObject $maintenance_wrapper;
    private Project $project;
    private MediawikiLanguageManager&MockObject $language_manager;
    private TestLogger $logger;
    private MediawikiDataDir&MockObject $mediawiki_data_dir;
    private MediawikiManager&MockObject $mediawiki_manager;
    private SimpleXMLElement $xml_tree;
    private ZipArchive&MockObject $zip;
    private vfsStreamDirectory $fixtures_dir;


    protected function setUp(): void
    {
        parent::setUp();

        $this->mediawiki_manager = $this->createMock(MediawikiManager::class);
        $this->project           = ProjectTestBuilder::aProject()->build();
        $this->language_manager  = $this->createMock(MediawikiLanguageManager::class);
        $this->language_manager->method('getUsedLanguageForProject')->willReturn('fr_FR');
        $this->maintenance_wrapper = $this->createMock(MediawikiMaintenanceWrapper::class);
        $this->mediawiki_data_dir  = $this->createMock(MediawikiDataDir::class);
        $this->logger              = new TestLogger();

        $data           = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';
        $this->xml_tree = new SimpleXMLElement($data);

        $structure = [
            'files' => [],
        ];

        $base_dir           = vfsStream::setup();
        $this->fixtures_dir = vfsStream::create($structure, $base_dir);
        chmod($this->fixtures_dir->getChild('files')->url(), '0777');

        $this->zip = $this->createMock(ZipArchive::class);

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
        $this->language_manager->expects($this->never())->method('getUsedLanguageForProject');

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withoutExportableMediawiki());

        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testItExportsMediaWikiAttributes(): void
    {
        $this->mediawiki_manager->method('getReadAccessControl')->willReturn([]);
        $this->mediawiki_manager->method('getWriteAccessControl')->willReturn([]);

        $this->mediawiki_data_dir->expects($this->once())->method('getMediawikiDir')->willReturn(vfsStream::setup()->url());

        $this->maintenance_wrapper->expects($this->once())->method('dumpBackupFull');
        $this->maintenance_wrapper->expects($this->once())->method('dumpUploads');

        $this->zip->expects($this->once())->method('addFile');

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

        $mediawiki = $this->xml_tree->mediawiki;
        $attrs     = $mediawiki->attributes();

        $this->assertEquals($attrs['pages-backup'], 'wiki_pages.xml');
        $this->assertEquals($attrs['language'], 'fr_FR');
        $this->assertEquals($attrs['files-folder-backup'], 'files');

        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testItExportsMediaWikiPermissions(): void
    {
        $this->mediawiki_manager->method('getReadAccessControl')->willReturn(
            [
                4,
                5,
            ]
        );
        $this->mediawiki_manager->method('getWriteAccessControl')->willReturn(
            [
                5,
            ]
        );

        $this->mediawiki_data_dir->expects($this->once())->method('getMediawikiDir')->willReturn(vfsStream::setup()->url());

        $this->maintenance_wrapper->expects($this->once())->method('dumpBackupFull');
        $this->maintenance_wrapper->expects($this->once())->method('dumpUploads');

        $this->zip->expects($this->once())->method('addFile');

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEquals((string) $readers[0], 'project-admins');
        $this->assertEquals((string) $readers[1], 'custom');

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertEquals((string) $writers[0], 'custom');

        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testItDoesNotExportReadPermissionsIfNoReadersAreDefined(): void
    {
        $this->mediawiki_manager->method('getReadAccessControl')->willReturn(
            null
        );
        $this->mediawiki_manager->method('getWriteAccessControl')->willReturn(
            [
                5,
            ]
        );

        $this->mediawiki_data_dir->expects($this->once())->method('getMediawikiDir')->willReturn(vfsStream::setup()->url());

        $this->maintenance_wrapper->expects($this->once())->method('dumpBackupFull');
        $this->maintenance_wrapper->expects($this->once())->method('dumpUploads');

        $this->zip->expects($this->once())->method('addFile');

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertFalse(isset($readers[0]));

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertEquals((string) $writers[0], 'custom');

        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testItDoesNotExportWritePermissionsIfNoWritersAreDefined(): void
    {
        $this->mediawiki_manager->method('getReadAccessControl')->willReturn(
            [
                4,
                5,
            ]
        );
        $this->mediawiki_manager->method('getWriteAccessControl')->willReturn(
            null
        );

        $this->mediawiki_data_dir->expects($this->once())->method('getMediawikiDir')->willReturn(vfsStream::setup()->url());

        $this->maintenance_wrapper->expects($this->once())->method('dumpBackupFull');
        $this->maintenance_wrapper->expects($this->once())->method('dumpUploads');

        $this->zip->expects($this->once())->method('addFile');

        $this->exportToXML(CheckXMLMediawikiExportabilityStub::withExportableMediawiki());

        $readers = $this->xml_tree->mediawiki->{'read-access'}->ugroup;
        $this->assertEquals((string) $readers[0], 'project-admins');
        $this->assertEquals((string) $readers[1], 'custom');

        $writers = $this->xml_tree->mediawiki->{'write-access'}->ugroup;
        $this->assertFalse(isset($writers[0]));

        self::assertTrue($this->logger->hasInfoRecords());
    }

    private function exportToXML(CheckXMLMediawikiExportability $check_xml_mediawiki_exportability): void
    {
        $ugroup_manager = $this->createMock(UGroupManager::class);
        $custom_ugroup  = $this->createMock(ProjectUGroup::class);
        $custom_ugroup->method('getName')->willReturn('custom');
        $custom_ugroup->method('getId');

        $project_admins_ugroups = $this->createMock(ProjectUGroup::class);
        $project_admins_ugroups->method('getName')->willReturn('project-admins');
        $project_admins_ugroups->method('getId');

        $ugroup_manager->method('getUGroup')->willReturnCallback(static fn(Project $project, int $ugroup_id) => match ($ugroup_id) {
            4 => $project_admins_ugroups,
            5 => $custom_ugroup,
        });

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
