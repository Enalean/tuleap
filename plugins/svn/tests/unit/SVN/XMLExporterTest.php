<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\SVN;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\SVNCore\SVNAccessFileReader;
use Tuleap\SVN\Admin\MailNotification;
use Tuleap\Test\Builders\ProjectTestBuilder;
use XML_SimpleXMLCDATAFactory;

final class XMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private SVNAccessFileReader&MockObject $access_file_reader;
    private XMLSvnExporter $xml_exporter;
    private ZipArchive&MockObject $zip;
    private SimpleXMLElement $xml_tree;
    private string $fixtures_dir;
    private SvnAdmin&MockObject $svn_admin;

    protected function setUp(): void
    {
        parent::setUp();

        $repository_manager        = $this->createMock(\Tuleap\SVN\Repository\RepositoryManager::class);
        $mail_notification_manager = $this->createMock(\Tuleap\SVN\Admin\MailNotificationManager::class);

        $this->access_file_reader = $this->createMock(SVNAccessFileReader::class);
        $this->svn_admin          = $this->createMock(SvnAdmin::class);

        $this->xml_exporter = new XMLSvnExporter(
            $repository_manager,
            ProjectTestBuilder::aProject()->build(),
            $this->svn_admin,
            new XML_SimpleXMLCDATAFactory(),
            $mail_notification_manager,
            new NullLogger(),
            $this->access_file_reader
        );

        $this->fixtures_dir = vfsStream::setup()->url();

        $this->zip = $this->createMock(ZipArchive::class);

        $repository = $this->createMock(\Tuleap\SVNCore\Repository::class);
        $repository->method('getName')->willReturn('MyRepo');
        $repository->method('getSystemPath')->willReturn($this->fixtures_dir);
        $repository_manager->method('getRepositoriesInProject')->willReturn([$repository]);

        $mail_notification_manager->method('getByRepository')->with($repository)->willReturn(
            [
                new MailNotification(
                    1,
                    $repository,
                    '/',
                    ['mail@example.com'],
                    [],
                    []
                ),
                new MailNotification(
                    2,
                    $repository,
                    '/trunk',
                    ['mail2@example.com'],
                    [],
                    []
                ),
            ]
        );

        $data = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';

        $this->xml_tree = new SimpleXMLElement($data);
    }

    public function testItExportSvnAttributes(): void
    {
        $this->zip->method('isADirectory')->willReturn(false);
        $this->zip->expects(self::once())->method('addFile');

        $this->access_file_reader->expects(self::once())->method('readContentBlock');

        $this->svn_admin->method('dumpRepository');

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;
            $attrs      = $repository->attributes();
            self::assertEquals($attrs['name'], 'MyRepo');
            self::assertEquals($attrs['dump-file'], 'svn/MyRepo.svn');
        }
    }

    public function testItExportsSVNAccessFile(): void
    {
        $expected_access_file = "[/tags]
@members = r";

        $this->zip->method('isADirectory')->willReturn(false);
        $this->zip->expects(self::once())->method('addFile');

        $this->access_file_reader->expects(self::once())->method('readContentBlock')->willReturn($expected_access_file);

        $this->svn_admin->method('dumpRepository');

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;

            self::assertEquals($expected_access_file, (string) $repository->{"access-file"});
        }
    }

    public function testItExportsNotifiedMails(): void
    {
        $this->zip->method('isADirectory')->willReturn(false);
        $this->zip->expects(self::once())->method('addFile');

        $this->access_file_reader->expects(self::once())->method('readContentBlock');

        $this->svn_admin->method('dumpRepository');

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;

            $notified_mails = $repository->notification[0];
            $attrs          = $notified_mails->attributes();
            self::assertEquals($attrs['path'], '/');
            self::assertEquals($attrs['emails'], 'mail@example.com');

            $notified_mails = $repository->notification[1];
            $attrs          = $notified_mails->attributes();
            self::assertEquals($attrs['path'], '/trunk');
            self::assertEquals($attrs['emails'], 'mail2@example.com');
        }
    }
}
