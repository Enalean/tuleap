<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Upload\Section\File\Tus;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Stubs\BuildArtidocWithContextRetrieverStub;
use Tuleap\Artidoc\Stubs\Domain\Document\RetrieveArtidocWithContextStub;
use Tuleap\Artidoc\Stubs\Upload\Section\File\SearchNotExpiredOngoingUploadStub;
use Tuleap\Artidoc\Upload\Section\File\UploadedFileWithArtidocRetriever;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CurrentRequestUserProviderStub;
use Tuleap\Tus\Identifier\UUIDFileIdentifierFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FileBeingUploadedInformationProviderTest extends TestCase
{
    private const ARTIDOC_ID = 123;

    public function testFileInformationCanBeProvided(): void
    {
        $identifier_factory = new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory());
        $id                 = $identifier_factory->buildIdentifier();

        $provider = new FileBeingUploadedInformationProvider(
            new UploadedFileWithArtidocRetriever(
                BuildArtidocWithContextRetrieverStub::withRetriever(
                    RetrieveArtidocWithContextStub::withDocumentUserCanWrite(
                        new ArtidocWithContext(
                            new ArtidocDocument(['item_id' => self::ARTIDOC_ID]),
                        ),
                    ),
                ),
            ),
            $identifier_factory,
            SearchNotExpiredOngoingUploadStub::withFile(
                new UploadFileInformation(
                    self::ARTIDOC_ID,
                    $id,
                    'Filename',
                    123,
                ),
            ),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
        );

        $server_request = (new NullServerRequest())
            ->withAttribute('id', $id->toString());

        $file_information = $provider->getFileInformation($server_request);

        self::assertNotNull($file_information);
        self::assertSame($id, $file_information->getID());
        self::assertSame(123, $file_information->getLength());
        self::assertSame(0, $file_information->getOffset());
    }

    public function testFileInformationCannotBeFoundIfUserCannotWriteTheDocument(): void
    {
        $identifier_factory = new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory());
        $id                 = $identifier_factory->buildIdentifier();

        $provider = new FileBeingUploadedInformationProvider(
            new UploadedFileWithArtidocRetriever(
                BuildArtidocWithContextRetrieverStub::withRetriever(
                    RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                        new ArtidocWithContext(
                            new ArtidocDocument(['item_id' => self::ARTIDOC_ID]),
                        ),
                    ),
                ),
            ),
            $identifier_factory,
            SearchNotExpiredOngoingUploadStub::withFile(
                new UploadFileInformation(
                    self::ARTIDOC_ID,
                    $id,
                    'Filename',
                    123,
                ),
            ),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
        );

        $server_request = (new NullServerRequest())
            ->withAttribute('id', $id->toString());

        $this->assertNull($provider->getFileInformation($server_request));
    }

    public function testFileInformationCannotBeFoundIfRequestAttributesAreMissing(): void
    {
        $identifier_factory = new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory());

        $provider = new FileBeingUploadedInformationProvider(
            new UploadedFileWithArtidocRetriever(
                BuildArtidocWithContextRetrieverStub::withRetriever(
                    RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                        new ArtidocWithContext(
                            new ArtidocDocument(['item_id' => self::ARTIDOC_ID]),
                        ),
                    ),
                ),
            ),
            $identifier_factory,
            SearchNotExpiredOngoingUploadStub::shouldNotBeCalled(),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
        );

        $server_request = (new NullServerRequest());

        $this->assertNull($provider->getFileInformation($server_request));
    }

    public function testFileInformationCannotBeFoundIfNoCurrentUserIsAssociatedWithTheRequest(): void
    {
        $identifier_factory = new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory());
        $id                 = $identifier_factory->buildIdentifier();

        $provider = new FileBeingUploadedInformationProvider(
            new UploadedFileWithArtidocRetriever(
                BuildArtidocWithContextRetrieverStub::withRetriever(
                    RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                        new ArtidocWithContext(
                            new ArtidocDocument(['item_id' => self::ARTIDOC_ID]),
                        ),
                    ),
                ),
            ),
            $identifier_factory,
            SearchNotExpiredOngoingUploadStub::shouldNotBeCalled(),
            new CurrentRequestUserProviderStub(null),
        );

        $server_request = (new NullServerRequest())
            ->withAttribute('id', $id->toString());

        $this->assertNull($provider->getFileInformation($server_request));
    }

    public function testFileInformationCannotBeFoundIfThereIsNotAValidEntryInTheDatabase(): void
    {
        $identifier_factory = new UUIDFileIdentifierFactory(new DatabaseUUIDV7Factory());
        $id                 = $identifier_factory->buildIdentifier();

        $provider = new FileBeingUploadedInformationProvider(
            new UploadedFileWithArtidocRetriever(
                BuildArtidocWithContextRetrieverStub::withRetriever(
                    RetrieveArtidocWithContextStub::withDocumentUserCanRead(
                        new ArtidocWithContext(
                            new ArtidocDocument(['item_id' => self::ARTIDOC_ID]),
                        ),
                    ),
                ),
            ),
            $identifier_factory,
            SearchNotExpiredOngoingUploadStub::withoutFile(),
            new CurrentRequestUserProviderStub(UserTestBuilder::buildWithDefaults()),
        );

        $server_request = (new NullServerRequest())
            ->withAttribute('id', $id->toString());

        $this->assertNull($provider->getFileInformation($server_request));
    }
}
