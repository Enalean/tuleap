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

namespace Tuleap\Docman\XML\Import;

use Docman_Item;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

class NodeImporterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ItemImporter
     */
    private $item_importer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostFileImporter
     */
    private $file_importer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostFolderImporter
     */
    private $folder_importer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostDoNothingImporter
     */
    private $do_nothing_importer;
    /**
     * @var NodeImporter
     */
    private $importer;
    /**
     * @var Docman_Item|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $parent_item;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->logger              = Mockery::mock(LoggerInterface::class);
        $this->item_importer       = Mockery::mock(ItemImporter::class);
        $this->file_importer       = Mockery::mock(PostFileImporter::class);
        $this->folder_importer     = Mockery::mock(PostFolderImporter::class);
        $this->do_nothing_importer = Mockery::mock(PostDoNothingImporter::class);

        $this->parent_item = Mockery::mock(Docman_Item::class);
        $this->user        = Mockery::mock(PFUser::class);

        $this->importer = new NodeImporter(
            $this->item_importer,
            $this->file_importer,
            $this->folder_importer,
            $this->do_nothing_importer,
            $this->logger
        );
    }

    public function testImportEmpty(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing empty: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with(
                $node,
                $this->importer,
                $this->do_nothing_importer,
                $this->parent_item,
                $this->user,
                Mockery::on(static function (ImportProperties $properties): bool {
                    return $properties->getItemTypeId() === PLUGIN_DOCMAN_ITEM_TYPE_EMPTY;
                })
            )->once();


        $this->importer->import($node, $this->parent_item, $this->user);
    }

    public function testImportWiki(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="wiki">
                <properties>
                    <title>My document</title>
                </properties>
                <pagename>MyWikiPage</pagename>
            </item>
            EOS
        );

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing wiki: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with(
                $node,
                $this->importer,
                $this->do_nothing_importer,
                $this->parent_item,
                $this->user,
                Mockery::on(static function (ImportProperties $properties): bool {
                    return $properties->getItemTypeId() === PLUGIN_DOCMAN_ITEM_TYPE_WIKI
                        && $properties->getWikiPage() === 'MyWikiPage';
                })
            )->once();


        $this->importer->import($node, $this->parent_item, $this->user);
    }

    public function testImportLink(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="link">
                <properties>
                    <title>My document</title>
                </properties>
                <url>https://example.test</url>
            </item>
            EOS
        );

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing link: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with(
                $node,
                $this->importer,
                $this->do_nothing_importer,
                $this->parent_item,
                $this->user,
                Mockery::on(static function (ImportProperties $properties): bool {
                    return $properties->getItemTypeId() === PLUGIN_DOCMAN_ITEM_TYPE_LINK
                        && $properties->getLinkUrl() === 'https://example.test';
                })
            )->once();


        $this->importer->import($node, $this->parent_item, $this->user);
    }

    public function testImportFolder(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="folder">
                <properties>
                    <title>My folder</title>
                </properties>
            </item>
            EOS
        );

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing folder: My folder')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with(
                $node,
                $this->importer,
                $this->folder_importer,
                $this->parent_item,
                $this->user,
                Mockery::on(static function (ImportProperties $properties): bool {
                    return $properties->getItemTypeId() === PLUGIN_DOCMAN_ITEM_TYPE_FOLDER;
                })
            )->once();


        $this->importer->import($node, $this->parent_item, $this->user);
    }

    public function testImportFile(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="file">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing file: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with(
                $node,
                $this->importer,
                $this->file_importer,
                $this->parent_item,
                $this->user,
                Mockery::on(static function (ImportProperties $properties): bool {
                    return $properties->getItemTypeId() === PLUGIN_DOCMAN_ITEM_TYPE_FILE;
                })
            )->once();


        $this->importer->import($node, $this->parent_item, $this->user);
    }

    public function testImportEmbedded(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="embeddedfile">
                <properties>
                    <title>My document</title>
                </properties>
            </item>
            EOS
        );

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing embeddedfile: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with(
                $node,
                $this->importer,
                $this->file_importer,
                $this->parent_item,
                $this->user,
                Mockery::on(static function (ImportProperties $properties): bool {
                    return $properties->getItemTypeId() === PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE;
                })
            )->once();


        $this->importer->import($node, $this->parent_item, $this->user);
    }

    public function testImportItemWithItsDescription(): void
    {
        $node = new SimpleXMLElement(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <item type="empty">
                <properties>
                    <title>My document</title>
                    <description>The description</description>
                </properties>
            </item>
            EOS
        );

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing empty: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with(
                $node,
                $this->importer,
                $this->do_nothing_importer,
                $this->parent_item,
                $this->user,
                Mockery::on(static function (ImportProperties $properties): bool {
                    return $properties->getDescription() === 'The description'
                        && $properties->getTitle() === 'My document';
                })
            )->once();


        $this->importer->import($node, $this->parent_item, $this->user);
    }
}
