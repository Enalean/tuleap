<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Service\DocumentServiceDocmanProxy;
use Tuleap\Artidoc\Document\ArtidocDocumentInformation;
use Tuleap\Artidoc\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\Artidoc\Stubs\Document\RetrieveArtidocStub;
use Tuleap\Artidoc\Stubs\Document\SaveSectionsStub;
use Tuleap\Artidoc\Stubs\Document\TransformRawSectionsToRepresentationStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class PUTSectionsHandlerTest extends TestCase
{
    private const PROJECT_ID = 101;

    private \PFUser $user;
    private \Docman_PermissionsManager & MockObject $permissions_manager;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $this->permissions_manager);
    }

    protected function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    public function testHappyPath(): void
    {
        $saver = SaveSectionsStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $dummy_collection = new PaginatedArtidocSectionRepresentationCollection([], 0);

        $service_docman = $this->createMock(ServiceDocman::class);
        $handler        = new PUTSectionsHandler(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                    $this->createMock(ServiceDocman::class),
                    DocumentServiceDocmanProxy::build($service_docman)
                ),
            ),
            TransformRawSectionsToRepresentationStub::withCollection($dummy_collection),
            $saver,
            new SectionIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($saver->isSaved(1));
        self::assertSame([102, 101], $saver->getSavedForId(1));
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $saver = SaveSectionsStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $handler = new PUTSectionsHandler(
            RetrieveArtidocStub::withoutDocument(),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
            $saver,
            new SectionIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenTheUserCannotLoadTheDocumentComposedOfNewSections(): void
    {
        $saver = SaveSectionsStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $service_docman = $this->createMock(ServiceDocman::class);
        $handler        = new PUTSectionsHandler(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                    $this->createMock(ServiceDocman::class),
                    DocumentServiceDocmanProxy::build($service_docman)
                ),
            ),
            TransformRawSectionsToRepresentationStub::withoutCollection(),
            $saver,
            new SectionIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $saver = SaveSectionsStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(false);

        $service_docman = $this->createMock(ServiceDocman::class);
        $handler        = new PUTSectionsHandler(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                    $this->createMock(ServiceDocman::class),
                    DocumentServiceDocmanProxy::build($service_docman)
                ),
            ),
            TransformRawSectionsToRepresentationStub::shouldNotBeCalled(),
            $saver,
            new SectionIdentifierFactory(new DatabaseUUIDV7Factory()),
        );

        $result = $handler->handle(
            1,
            [
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(102),
                ),
                new ArtidocPUTSectionRepresentation(
                    new ArtidocPUTAndPOSTSectionArtifactRepresentation(101),
                ),
            ],
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }
}
