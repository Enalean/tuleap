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

namespace Tuleap\SVN;

require_once __DIR__ . '/../bootstrap.php';

use ForgeConfig;
use SimpleXMLElement;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\SVN\Admin\MailNotification;
use TuleapTestCase;
use XML_SimpleXMLCDATAFactory;

class XMLSvnExporterTest extends TuleapTestCase
{
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

    public function setUp()
    {
        parent::setUp();

        $repository_manager        = mock('Tuleap\SVN\Repository\RepositoryManager');
        $mail_notification_manager = mock('Tuleap\SVN\Admin\MailNotificationManager');

        $this->xml_exporter = new XMLSvnExporter(
            $repository_manager,
            mock('Project'),
            mock('Tuleap\SVN\SvnAdmin'),
            new XML_SimpleXMLCDATAFactory(),
            $mail_notification_manager,
            mock('System_Command'),
            mock('Tuleap\SVN\SvnLogger')
        );

        $this->fixtures_dir = dirname(__FILE__) . '/../_fixtures';

        $this->zip = new ZipArchive($this->getTmpDir() . '/archive.zip');

        $repository = mock('Tuleap\SVN\Repository\Repository');
        stub($repository)->getName()->returns('MyRepo');
        stub($repository)->getSystemPath()->returns($this->fixtures_dir);

        stub($repository_manager)->getRepositoriesInProject()->returns(array($repository));

        stub($mail_notification_manager)->getByRepository($repository)->returns(
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

        ForgeConfig::store();
        ForgeConfig::set('tmp_dir', $this->getTmpDir());

        mkdir($this->getTmpDir() . '/tmp/export/svn', 0770, true);
        touch($this->getTmpDir() . '/MyRepo.svn');

        $data = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';

        $this->xml_tree = new SimpleXMLElement($data);
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        $this->zip->close();
        parent::tearDown();
    }


    public function itExportSvnAttributes()
    {
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;
            $attrs      = $repository->attributes();
            $this->assertEqual($attrs['name'], 'MyRepo');
            $this->assertEqual($attrs['dump-file'], 'svn/MyRepo.svn');
        }
    }

    public function itExportsSVNAccessFile()
    {
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;

            $expected_access_file = "[/tags]
@members = r";

            $this->assertEqual((string) $repository->{"access-file"}, $expected_access_file);
        }
    }

    public function itExportsNotifiedMails()
    {
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, $this->fixtures_dir);

        foreach ($this->xml_tree->svn as $exported_xml) {
            $repository = $exported_xml->repository;

            $notified_mails = $repository->notification[0];
            $attrs          = $notified_mails->attributes();
            $this->assertEqual($attrs['path'], '/');
            $this->assertEqual($attrs['emails'], 'mail@example.com');

            $notified_mails = $repository->notification[1];
            $attrs          = $notified_mails->attributes();
            $this->assertEqual($attrs['path'], '/trunk');
            $this->assertEqual($attrs['emails'], 'mail2@example.com');
        }
    }
}
