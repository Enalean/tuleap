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

use DateTimeImmutable;
use Docman_Item;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\xml\InvalidDateException;
use User\XML\Import\UserNotFoundException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NodeImporterTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private ItemImporter&MockObject $item_importer;
    private PostFileImporter&MockObject $file_importer;
    private PostFolderImporter&MockObject $folder_importer;
    private PostDoNothingImporter $do_nothing_importer;
    private NodeImporter $importer;
    private Docman_Item $parent_item;
    private PFUser $user;
    private ImportPropertiesExtractor&MockObject $properties_extractor;

    protected function setUp(): void
    {
        $this->logger               = $this->createMock(LoggerInterface::class);
        $this->item_importer        = $this->createMock(ItemImporter::class);
        $this->file_importer        = $this->createMock(PostFileImporter::class);
        $this->folder_importer      = $this->createMock(PostFolderImporter::class);
        $this->properties_extractor = $this->createMock(ImportPropertiesExtractor::class);
        $this->do_nothing_importer  = new PostDoNothingImporter();

        $this->parent_item = new Docman_Item();
        $this->user        = UserTestBuilder::buildWithDefaults();

        $this->importer = new NodeImporter(
            $this->item_importer,
            $this->file_importer,
            $this->folder_importer,
            $this->do_nothing_importer,
            $this->logger,
            $this->properties_extractor
        );
    }

    public function testImportCannotInstantiateItemWeHaveJustCreatedInDBException(): void
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

        $properties = ImportProperties::buildFile(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing file: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->willThrowException(new CannotInstantiateItemWeHaveJustCreatedInDBException());
        $this->logger->expects(self::once())->method('error')->with('An error occurred while creating in DB the item: ' . $node->properties->title);

        $this->importer->import($node, $this->parent_item);
    }

    public function testImportInvalidDateException(): void
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

        $properties = ImportProperties::buildFile(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing file: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->willThrowException(new InvalidDateException());
        $this->logger->expects(self::once())->method('error');

        $this->importer->import($node, $this->parent_item);
    }

    public function testImportUnknownItemTypeException(): void
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

        $properties = ImportProperties::buildFile(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing file: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->willThrowException(new UnknownItemTypeException(''));
        $this->logger->expects(self::once())->method('error');

        $this->importer->import($node, $this->parent_item);
    }

    public function testImportUserNotFoundException(): void
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

        $properties = ImportProperties::buildFile(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing file: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->willThrowException(new UserNotFoundException());
        $this->logger->expects(self::once())->method('error');

        $this->importer->import($node, $this->parent_item);
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

        $properties = ImportProperties::buildEmpty(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing empty: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->do_nothing_importer, $this->parent_item, $properties);

        $this->importer->import($node, $this->parent_item);
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

        $properties = ImportProperties::buildLink(
            'My document',
            '',
            'https://example.test',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing link: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->do_nothing_importer, $this->parent_item, $properties);

        $this->importer->import($node, $this->parent_item);
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

        $properties = ImportProperties::buildFolder(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing folder: My folder');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->folder_importer, $this->parent_item, $properties);

        $this->importer->import($node, $this->parent_item);
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

        $properties = ImportProperties::buildFile(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing file: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties);

        $this->importer->import($node, $this->parent_item);
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

        $properties = ImportProperties::buildEmbedded(
            'My document',
            '',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor->expects(self::once())->method('getImportProperties')->with($node)->willReturn($properties);

        $this->logger->expects(self::once())->method('debug')->with('Importing embeddedfile: My document');
        $this->item_importer->expects(self::once())->method('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties);

        $this->importer->import($node, $this->parent_item);
    }
}
