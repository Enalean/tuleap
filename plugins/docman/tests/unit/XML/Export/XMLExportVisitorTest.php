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

use ColinODell\PsrTestLogger\TestLogger;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Link;
use Docman_Version;
use Docman_VersionFactory;
use Docman_Wiki;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use PrioritizedList;
use SimpleXMLElement;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class XMLExportVisitorTest extends TestCase
{
    private TestLogger $logger;
    private ArchiveInterface&MockObject $archive;
    private XMLExportVisitor $visitor;
    private Docman_VersionFactory&MockObject $version_factory;
    private UserManager&MockObject $user_manager;
    private UserXMLExportedCollection&MockObject $user_collection;
    private PermissionsExporter&MockObject $perms_exporter;

    protected function setUp(): void
    {
        $this->version_factory = $this->createMock(Docman_VersionFactory::class);
        $this->logger          = new TestLogger();
        $this->archive         = $this->createMock(ArchiveInterface::class);
        $this->user_manager    = $this->createMock(UserManager::class);
        $this->user_collection = $this->createMock(UserXMLExportedCollection::class);
        $this->perms_exporter  = $this->createMock(PermissionsExporter::class);
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
        $empty = new Docman_Empty(['title' => 'My document', 'description' => 'desc', 'item_id' => 42]);
        $xml   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->expects($this->once())->method('exportPermissions');

        $this->visitor->export($xml, $empty);

        self::assertTrue($this->logger->hasDebug('Exporting empty item #42: My document'));
        self::assertEquals(
            '<item type="empty"><properties><title><![CDATA[My document]]></title><description><![CDATA[desc]]></description></properties></item>',
            $xml->item->asXML()
        );
    }

    public function testWiki(): void
    {
        $wiki = new Docman_Wiki(['title' => 'My document', 'item_id' => 42, 'wiki_page' => 'WikiPage']);
        $xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->visitor->export($xml, $wiki);

        self::assertTrue($this->logger->hasWarning('Cannot export wiki item #42 (My document). Export/import of wiki documents is not supported.'));
        self::assertEmpty($xml->item);
    }

    public function testOtherDocument(): void
    {
        $other = new class extends OtherDocument {
            public function __construct()
            {
                parent::__construct(['title' => 'My document', 'item_id' => 42]);
            }
        };

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->visitor->export($xml, $other);

        self::assertTrue($this->logger->hasWarning('Cannot export item #42 (My document). Export/import of other type of documents is not supported.'));
        self::assertEmpty($xml->item);
    }

    public function testLink(): void
    {
        $link = new Docman_Link(['title' => 'My document', 'item_id' => 42, 'link_url' => 'https://example.test']);
        $xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->expects($this->once())->method('exportPermissions');

        $this->visitor->export($xml, $link);

        self::assertTrue($this->logger->hasDebug('Exporting link item #42: My document'));
        self::assertEquals(
            '<item type="link"><properties><title><![CDATA[My document]]></title></properties><url><![CDATA[https://example.test]]></url></item>',
            $xml->item->asXML()
        );
    }

    public function testFile(): void
    {
        $file = new Docman_File(['title' => 'My document', 'item_id' => 42]);
        $xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->expects($this->once())->method('exportPermissions');

        $this->version_factory->expects($this->once())->method('getAllVersionForItem')->willReturn([
            new Docman_Version(
                [
                    'id'       => 241,
                    'path'     => '/titi',
                    'filetype' => 'image/png',
                    'filename' => 'titi.png',
                    'label'    => 'The label',
                ]
            ),
            new Docman_Version(
                [
                    'id'        => 142,
                    'path'      => '/toto',
                    'filetype'  => 'image/png',
                    'filename'  => 'toto.png',
                    'date'      => 1234567890,
                    'changelog' => 'The changelog',
                ]
            ),
        ]);
        $matcher = self::exactly(2);
        $this->archive->expects($matcher)->method('addFile')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('documents/content-142.bin', $parameters[0]);
                self::assertSame('/toto', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('documents/content-241.bin', $parameters[0]);
                self::assertSame('/titi', $parameters[1]);
            }
        });

        $this->visitor->export($xml, $file);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        self::assertTrue($this->logger->hasDebug('Exporting file item #42: My document'));
        self::assertEquals(
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
                    <date format="ISO8601"><![CDATA[2009-02-14T00:31:30+01:00]]></date>
                    <changelog><![CDATA[The changelog]]></changelog>
                    <content><![CDATA[documents/content-142.bin]]></content>
                  </version>
                  <version>
                    <filename><![CDATA[titi.png]]></filename>
                    <filetype><![CDATA[image/png]]></filetype>
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

    public function testFileWithNoVersionMustBeRNCValidated(): void
    {
        $file = new Docman_File(['title' => 'My document', 'item_id' => 42]);
        $xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->expects($this->once())->method('exportPermissions');

        $this->version_factory->expects($this->once())->method('getAllVersionForItem')->willReturn([]);
        $this->archive->expects($this->never())->method('addFile');

        $this->visitor->export($xml, $file);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        self::assertTrue($this->logger->hasDebug('Exporting file item #42: My document'));
        self::assertEquals(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <docman>
              <item type="file">
                <properties>
                  <title><![CDATA[My document]]></title>
                </properties>
                <versions/>
              </item>
            </docman>

            EOS,
            $dom->saveXML()
        );
    }

    public function testEmbedded(): void
    {
        $embedded_file = new Docman_EmbeddedFile(['title' => 'My document', 'item_id' => 42]);
        $xml           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->expects($this->once())->method('exportPermissions');

        $this->version_factory->expects($this->once())->method('getAllVersionForItem')->willReturn([
            new Docman_Version(
                [
                    'id'       => 241,
                    'path'     => '/titi',
                    'filetype' => 'image/png',
                    'filename' => 'file',
                ]
            ),
            new Docman_Version(
                [
                    'id'       => 142,
                    'path'     => '/toto',
                    'filetype' => 'image/png',
                    'filename' => 'file',
                ]
            ),
        ]);
        $matcher = self::exactly(2);
        $this->archive->expects($matcher)->method('addFile')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame('documents/content-142.bin', $parameters[0]);
                self::assertSame('/toto', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame('documents/content-241.bin', $parameters[0]);
                self::assertSame('/titi', $parameters[1]);
            }
        });

        $this->visitor->export($xml, $embedded_file);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        self::assertTrue($this->logger->hasDebug('Exporting embeddedfile item #42: My document'));
        self::assertEquals(
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
                    <content><![CDATA[documents/content-142.bin]]></content>
                  </version>
                  <version>
                    <filename><![CDATA[file]]></filename>
                    <filetype><![CDATA[image/png]]></filetype>
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
        $xml    = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');
        $folder = new Docman_Folder(['title' => 'My folder', 'item_id' => 42]);
        $folder->setItems(new PrioritizedList([
            new Docman_Empty(['title' => 'My sub document', 'item_id' => 43]),
            new Docman_Folder(['title' => 'My sub folder', 'item_id' => 44]),
        ]));

        $this->perms_exporter->expects($this->exactly(3))->method('exportPermissions');

        $this->visitor->export($xml, $folder);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        self::assertTrue($this->logger->hasDebug('Exporting folder item #42: My folder'));
        self::assertTrue($this->logger->hasDebug('Exporting empty item #43: My sub document'));
        self::assertTrue($this->logger->hasDebug('Exporting folder item #44: My sub folder'));
        self::assertEquals(
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
        $embedded_file = new Docman_EmbeddedFile(['title' => 'My document', 'item_id' => 42, 'user_id' => 103]);
        $xml           = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><docman />');

        $this->perms_exporter->expects($this->once())->method('exportPermissions');

        $this->version_factory->expects($this->once())->method('getAllVersionForItem')->willReturn([
            new Docman_Version([
                'id'       => 241,
                'path'     => '/titi',
                'filetype' => 'image/png',
                'filename' => 'file',
                'user_id'  => 104,
            ]),
        ]);
        $this->archive->method('addFile');

        $project_admin  = UserTestBuilder::aUser()->withLdapId('103')->build();
        $project_member = UserTestBuilder::aUser()->withLdapId('104')->build();
        $this->user_manager->expects($this->exactly(2))->method('getUserById')
            ->willReturnCallback(static fn(int $id) => match ($id) {
                103 => $project_admin,
                104 => $project_member,
            });
        $this->user_collection->expects($this->exactly(2))->method('add')
            ->with(self::callback(static fn(PFUser $user) => $user === $project_member || $user === $project_admin));

        $this->visitor->export($xml, $embedded_file);

        $dom               = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;
        self::assertEquals(
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
