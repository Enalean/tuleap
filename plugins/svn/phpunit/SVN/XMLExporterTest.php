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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalSVNPollution;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\SVN\AccessControl\AccessFileReader;
use Tuleap\SVN\Admin\MailNotification;
use XML_SimpleXMLCDATAFactory;

final class XMLExporterTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox, GlobalSVNPollution;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessFileReader
     */
    private $access_file_reader;
    /**
     * @var XMLSvnExporter
     */
    private $xml_exporter;

    /**
     * @var ZipArchive
     */
    private $zip;

    /**
     * @var SimpleXMLElement
     */
    private $xml_tree;

    private $fixtures_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $repository_manager        = \Mockery::spy(\Tuleap\SVN\Repository\RepositoryManager::class);
        $mail_notification_manager = \Mockery::spy(\Tuleap\SVN\Admin\MailNotificationManager::class);

        $this->access_file_reader = \Mockery::mock(AccessFileReader::class);

        $this->xml_exporter = new XMLSvnExporter(
            $repository_manager,
            \Mockery::spy(\Project::class),
            \Mockery::spy(\Tuleap\SVN\SvnAdmin::class),
            new XML_SimpleXMLCDATAFactory(),
            $mail_notification_manager,
            \Mockery::spy(\Psr\Log\LoggerInterface::class),
            $this->access_file_reader
        );

        $this->fixtures_dir = vfsStream::setup()->url();

        $this->zip = \Mockery::mock(ZipArchive::class);

        $repository = \Mockery::spy(\Tuleap\SVN\Repository\Repository::class);
        $repository->shouldReceive('getName')->andReturn('MyRepo');
        $repository->shouldReceive('getSystemPath')->andReturn($this->fixtures_dir);
        $repository_manager->shouldReceive('getRepositoriesInProject')->andReturn(array($repository));

        $mail_notification_manager->shouldReceive('getByRepository')->withArgs([$repository])->andReturn(
            array(
                new MailNotification(
                    1,
                    $repository,
                    '/',
                    array('mail@example.com'),
                    array(),
                    array()
                ),
                new MailNotification(
                    2,
                    $repository,
                    '/trunk',
                    array('mail2@example.com'),
                    array(),
                    array()
                )
            )
        );

        $data = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';

        $this->xml_tree = new SimpleXMLElement($data);
    }

    public function testItExportSvnAttributes(): void
    {
        $this->zip->shouldReceive('isADirectory')->andReturnFalse();
        $this->zip->shouldReceive('addFile')->once();

        $this->access_file_reader->shouldReceive('readContentBlock')->once();

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;
            $attrs      = $repository->attributes();
            $this->assertEquals($attrs['name'], 'MyRepo');
            $this->assertEquals($attrs['dump-file'], 'svn/MyRepo.svn');
        }
    }

    public function testItExportsSVNAccessFile(): void
    {
        $expected_access_file = "[/tags]
@members = r";

        $this->zip->shouldReceive('isADirectory')->andReturnFalse();
        $this->zip->shouldReceive('addFile')->once();

        $this->access_file_reader->shouldReceive('readContentBlock')->once()->andReturn($expected_access_file);

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;

            $this->assertEquals($expected_access_file, (string) $repository->{"access-file"});
        }
    }

    public function testItExportsNotifiedMails(): void
    {
        $this->zip->shouldReceive('isADirectory')->andReturnFalse();
        $this->zip->shouldReceive('addFile')->once();

        $this->access_file_reader->shouldReceive('readContentBlock')->once();

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;

            $notified_mails = $repository->notification[0];
            $attrs          = $notified_mails->attributes();
            $this->assertEquals($attrs['path'], '/');
            $this->assertEquals($attrs['emails'], 'mail@example.com');

            $notified_mails = $repository->notification[1];
            $attrs          = $notified_mails->attributes();
            $this->assertEquals($attrs['path'], '/trunk');
            $this->assertEquals($attrs['emails'], 'mail2@example.com');
        }
    }
}
