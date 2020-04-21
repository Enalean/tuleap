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
use Tuleap\Docman\CannotInstantiateItemWeHaveJustCreatedInDBException;
use Tuleap\xml\InvalidDateException;
use User\XML\Import\UserNotFoundException;

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
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ImportPropertiesExtractor
     */
    private $properties_extractor;

    protected function setUp(): void
    {
        $this->logger               = Mockery::mock(LoggerInterface::class);
        $this->item_importer        = Mockery::mock(ItemImporter::class);
        $this->file_importer        = Mockery::mock(PostFileImporter::class);
        $this->folder_importer      = Mockery::mock(PostFolderImporter::class);
        $this->do_nothing_importer  = Mockery::mock(PostDoNothingImporter::class);
        $this->properties_extractor = Mockery::mock(ImportPropertiesExtractor::class);

        $this->parent_item = Mockery::mock(Docman_Item::class);
        $this->user        = Mockery::mock(PFUser::class);

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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing file: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->once()
            ->andThrow(CannotInstantiateItemWeHaveJustCreatedInDBException::class);
        $this->logger
            ->shouldReceive('error')
            ->with('An error occurred while creating in DB the item: ' . $node->properties->title)
            ->once();

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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing file: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->once()
            ->andThrow(InvalidDateException::class);
        $this->logger
            ->shouldReceive('error')
            ->once();

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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing file: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->once()
            ->andThrow(UnknownItemTypeException::class);
        $this->logger
            ->shouldReceive('error')
            ->once();

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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing file: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->once()
            ->andThrow(UserNotFoundException::class);
        $this->logger
            ->shouldReceive('error')
            ->once();

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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing empty: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->do_nothing_importer, $this->parent_item, $properties)
            ->once();


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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing link: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->do_nothing_importer, $this->parent_item, $properties)
            ->once();


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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing folder: My folder')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->folder_importer, $this->parent_item, $properties)
            ->once();


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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing file: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->once();


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
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            $this->user
        );
        $this->properties_extractor
            ->shouldReceive('getImportProperties')
            ->with($node)
            ->once()
            ->andReturn($properties);

        $this->logger
            ->shouldReceive('debug')
            ->with('Importing embeddedfile: My document')
            ->once();
        $this->item_importer
            ->shouldReceive('import')
            ->with($node, $this->importer, $this->file_importer, $this->parent_item, $properties)
            ->once();


        $this->importer->import($node, $this->parent_item);
    }
}
