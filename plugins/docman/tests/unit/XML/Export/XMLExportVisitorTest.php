<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\XML\Export;

use Docman_Version;
use Docman_VersionFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Project\XML\Export\ArchiveInterface;
use UserXMLExporter;

class XMLExportVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArchiveInterface
     */
    private $archive;
    /**
     * @var XMLExportVisitor
     */
    private $visitor;
    /**
     * @var Docman_VersionFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $version_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserXMLExportedCollection
     */
    private $user_collection;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PermissionsExporter
     */
    private $perms_exporter;

    protected function setUp(): void
    {
        $this->version_factory = Mockery::mock(Docman_VersionFactory::class);
        $this->logger          = Mockery::mock(LoggerInterface::class);
        $this->archive         = Mockery::mock(ArchiveInterface::class);
        $this->user_manager    = Mockery::mock(\UserManager::class);
        $this->user_collection = Mockery::mock(\UserXMLExportedCollection::class);
        $this->perms_exporter  = Mockery::mock(PermissionsExporter::class);
        $user_exporter         = new UserXMLExporter($this->user_manager, $this->user_collection);

        $this->visitor = new XMLExportVisitor(
            $this->logger,
            $this->archive,
            $this->version_factory,
            $user_exporter,
            $this->perms_exporter
        );
    }

    public function testEmpty(): void
    {
        $empty = new \Docman_Empty(['title' => 'My document', 'description' => 'desc', 'item_id' => 42]);
        $xml   = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->shouldReceive('exportPermissions')->once();

        $this->logger->shouldReceive('debug')->with('Exporting empty item #42: My document')->once();
        $this->visitor->export($xml, $empty);

        $this->assertEquals(
            '<item type="empty"><properties><title><![CDATA[My document]]></title><description><![CDATA[desc]]></description></properties></item>',
            $xml->item->asXML()
        );
    }

    public function testWiki(): void
    {
        $wiki = new \Docman_Wiki(['title' => 'My document', 'item_id' => 42, 'wiki_page' => 'WikiPage']);
        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->logger->shouldReceive('warning')->with('Cannot export wiki item #42 (My document). Export/import of wiki documents is not supported.')->once();
        $this->visitor->export($xml, $wiki);

        $this->assertEmpty($xml->item);
    }

    public function testLink(): void
    {
        $link = new \Docman_Link(['title' => 'My document', 'item_id' => 42, 'link_url' => 'https://example.test']);
        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->shouldReceive('exportPermissions')->once();

        $this->logger->shouldReceive('debug')->with('Exporting link item #42: My document')->once();
        $this->visitor->export($xml, $link);

        $this->assertEquals(
            '<item type="link"><properties><title><![CDATA[My document]]></title></properties><url><![CDATA[https://example.test]]></url></item>',
            $xml->item->asXML()
        );
    }

    public function testFile(): void
    {
        $file = new \Docman_File(['title' => 'My document', 'item_id' => 42]);
        $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->shouldReceive('exportPermissions')->once();

        $this->logger->shouldReceive('debug')->with('Exporting file item #42: My document')->once();
        $this->version_factory
            ->shouldReceive('getAllVersionForItem')
            ->andReturn(
                [
                    new Docman_Version(
                        [
                            'id'       => 241,
                            'path'     => '/titi',
                            'filetype' => 'image/png',
                            'filesize' => 4096,
                            'filename' => 'titi.png',
                            'label'    => 'The label'
                        ]
                    ),
                    new Docman_Version(
                        [
                            'id'        => 142,
                            'path'      => '/toto',
                            'filetype'  => 'image/png',
                            'filesize'  => 256,
                            'filename'  => 'toto.png',
                            'date'      => 1234567890,
                            'changelog' => 'The changelog'
                        ]
                    ),
                ]
            )->once();
        $this->archive->shouldReceive('addFile')->with('documents/content-142.bin', '/toto')->once();
        $this->archive->shouldReceive('addFile')->with('documents/content-241.bin', '/titi')->once();

        $this->visitor->export($xml, $file);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $this->assertEquals(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
              <item type="file">
                <properties>
                  <title><![CDATA[My document]]></title>
                </properties>
                <versions>
                  <version>
                    <filename><![CDATA[toto.png]]></filename>
                    <filetype><![CDATA[image/png]]></filetype>
                    <filesize><![CDATA[256]]></filesize>
                    <date format="ISO8601"><![CDATA[2009-02-14T00:31:30+01:00]]></date>
                    <changelog><![CDATA[The changelog]]></changelog>
                    <content><![CDATA[documents/content-142.bin]]></content>
                  </version>
                  <version>
                    <filename><![CDATA[titi.png]]></filename>
                    <filetype><![CDATA[image/png]]></filetype>
                    <filesize><![CDATA[4096]]></filesize>
                    <label><![CDATA[The label]]></label>
                    <content><![CDATA[documents/content-241.bin]]></content>
                  </version>
                </versions>
              </item>
            </docman>

            EOS,
            $dom->saveXML()
        );
    }

    public function testEmbedded(): void
    {
        $embedded_file = new \Docman_EmbeddedFile(['title' => 'My document', 'item_id' => 42]);
        $xml           = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->shouldReceive('exportPermissions')->once();

        $this->logger->shouldReceive('debug')->with('Exporting embeddedfile item #42: My document')->once();
        $this->version_factory
            ->shouldReceive('getAllVersionForItem')
            ->andReturn(
                [
                    new Docman_Version(
                        [
                            'id'       => 241,
                            'path'     => '/titi',
                            'filetype' => 'image/png',
                            'filesize' => 4096,
                            'filename' => 'file'
                        ]
                    ),
                    new Docman_Version(
                        [
                            'id'       => 142,
                            'path'     => '/toto',
                            'filetype' => 'image/png',
                            'filesize' => 256,
                            'filename' => 'file'
                        ]
                    ),
                ]
            )->once();
        $this->archive->shouldReceive('addFile')->with('documents/content-142.bin', '/toto')->once();
        $this->archive->shouldReceive('addFile')->with('documents/content-241.bin', '/titi')->once();

        $this->visitor->export($xml, $embedded_file);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $this->assertEquals(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
              <item type="embeddedfile">
                <properties>
                  <title><![CDATA[My document]]></title>
                </properties>
                <versions>
                  <version>
                    <filename><![CDATA[file]]></filename>
                    <filetype><![CDATA[image/png]]></filetype>
                    <filesize><![CDATA[256]]></filesize>
                    <content><![CDATA[documents/content-142.bin]]></content>
                  </version>
                  <version>
                    <filename><![CDATA[file]]></filename>
                    <filetype><![CDATA[image/png]]></filetype>
                    <filesize><![CDATA[4096]]></filesize>
                    <content><![CDATA[documents/content-241.bin]]></content>
                  </version>
                </versions>
              </item>
            </docman>

            EOS,
            $dom->saveXML()
        );
    }

    public function testFolder(): void
    {
        $xml    = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');
        $folder = new \Docman_Folder(['title' => 'My folder', 'item_id' => 42]);
        $folder->setItems(
            new \PrioritizedList(
                [
                    new \Docman_Empty(['title' => 'My sub document', 'item_id' => 43]),
                    new \Docman_Folder(['title' => 'My sub folder', 'item_id' => 44]),
                ]
            )
        );

        $this->perms_exporter->shouldReceive('exportPermissions')->times(3);

        $this->logger->shouldReceive('debug')->with('Exporting folder item #42: My folder')->once();
        $this->logger->shouldReceive('debug')->with('Exporting empty item #43: My sub document')->once();
        $this->logger->shouldReceive('debug')->with('Exporting folder item #44: My sub folder')->once();

        $this->visitor->export($xml, $folder);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $this->assertEquals(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
              <item type="folder">
                <properties>
                  <title><![CDATA[My folder]]></title>
                </properties>
                <item type="empty">
                  <properties>
                    <title><![CDATA[My sub document]]></title>
                  </properties>
                </item>
                <item type="folder">
                  <properties>
                    <title><![CDATA[My sub folder]]></title>
                  </properties>
                </item>
              </item>
            </docman>

            EOS,
            $dom->saveXML()
        );
    }

    public function testItExportUsers(): void
    {
        $embedded_file = new \Docman_EmbeddedFile(['title' => 'My document', 'item_id' => 42, 'user_id' => 103]);
        $xml           = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->shouldReceive('exportPermissions')->once();

        $this->logger->shouldReceive('debug');
        $this->version_factory
            ->shouldReceive('getAllVersionForItem')
            ->andReturn(
                [
                    new Docman_Version(
                        [
                            'id'       => 241,
                            'path'     => '/titi',
                            'filetype' => 'image/png',
                            'filesize' => 4096,
                            'filename' => 'file',
                            'user_id'  => 104
                        ]
                    )
                ]
            )->once();
        $this->archive->shouldReceive('addFile');

        $project_admin  = Mockery::mock(\PFUser::class)->shouldReceive(['getLdapId' => 103])->getMock();
        $project_member = Mockery::mock(\PFUser::class)->shouldReceive(['getLdapId' => 104])->getMock();
        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(103)
            ->once()
            ->andReturn($project_admin);
        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(104)
            ->once()
            ->andReturn($project_member);
        $this->user_collection
            ->shouldReceive('add')
            ->with($project_admin)
            ->once();
        $this->user_collection
            ->shouldReceive('add')
            ->with($project_member)
            ->once();

        $this->visitor->export($xml, $embedded_file);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        $this->assertEquals(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
              <item type="embeddedfile">
                <properties>
                  <title><![CDATA[My document]]></title>
                  <owner format="ldap">103</owner>
                </properties>
                <versions>
                  <version>
                    <filename><![CDATA[file]]></filename>
                    <filetype><![CDATA[image/png]]></filetype>
                    <filesize><![CDATA[4096]]></filesize>
                    <author format="ldap">104</author>
                    <content><![CDATA[documents/content-241.bin]]></content>
                  </version>
                </versions>
              </item>
            </docman>

            EOS,
            $dom->saveXML()
        );
    }
}
