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
use Tuleap\Artidoc\Stubs\Document\RetrieveArtidocStub;
use Tuleap\Artidoc\Stubs\Document\SaveConfiguredTrackerStub;
use Tuleap\Artidoc\Stubs\Document\Tracker\CheckTrackerIsSuitableForDocumentStub;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

final class PUTConfigurationHandlerTest extends TestCase
{
    private const PROJECT_ID = 101;
    private const TRACKER_ID = 1001;

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
        $saver = SaveConfiguredTrackerStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(self::TRACKER_ID)
            ->build();

        $service_docman = $this->createMock(ServiceDocman::class);
        $handler        = new PUTConfigurationHandler(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                    $this->createMock(ServiceDocman::class),
                    DocumentServiceDocmanProxy::build($service_docman)
                ),
            ),
            $saver,
            RetrieveTrackerStub::withTracker($tracker),
            CheckTrackerIsSuitableForDocumentStub::withSuitableTrackers($tracker),
        );

        $result = $handler->handle(
            1,
            new ArtidocPUTConfigurationRepresentation([self::TRACKER_ID]),
            $this->user,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($saver->isSaved(1));
        self::assertSame(self::TRACKER_ID, $saver->getSavedForId(1));
    }

    public function testFaultWhenDocumentCannotBeRetrieved(): void
    {
        $saver = SaveConfiguredTrackerStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $handler = new PUTConfigurationHandler(
            RetrieveArtidocStub::withoutDocument(),
            $saver,
            RetrieveTrackerStub::withTracker(
                TrackerTestBuilder::aTracker()
                    ->withUserCanView(true)
                    ->withId(self::TRACKER_ID)
                    ->build(),
            ),
            CheckTrackerIsSuitableForDocumentStub::shouldNotBeCalled(),
        );

        $result = $handler->handle(
            1,
            new ArtidocPUTConfigurationRepresentation([self::TRACKER_ID]),
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenDocumentIsNotWritable(): void
    {
        $saver = SaveConfiguredTrackerStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(false);

        $service_docman = $this->createMock(ServiceDocman::class);
        $handler        = new PUTConfigurationHandler(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                    $this->createMock(ServiceDocman::class),
                    DocumentServiceDocmanProxy::build($service_docman)
                ),
            ),
            $saver,
            RetrieveTrackerStub::withTracker(
                TrackerTestBuilder::aTracker()
                    ->withUserCanView(true)
                    ->withId(self::TRACKER_ID)
                    ->build(),
            ),
            CheckTrackerIsSuitableForDocumentStub::shouldNotBeCalled(),
        );

        $result = $handler->handle(
            1,
            new ArtidocPUTConfigurationRepresentation([self::TRACKER_ID]),
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UserCannotWriteDocumentFault::class, $result->error);
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenTrackerDoesNotExist(): void
    {
        $saver = SaveConfiguredTrackerStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $service_docman = $this->createMock(ServiceDocman::class);
        $handler        = new PUTConfigurationHandler(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                    $this->createMock(ServiceDocman::class),
                    DocumentServiceDocmanProxy::build($service_docman)
                ),
            ),
            $saver,
            RetrieveTrackerStub::withoutTracker(),
            CheckTrackerIsSuitableForDocumentStub::shouldNotBeCalled(),
        );

        $result = $handler->handle(
            1,
            new ArtidocPUTConfigurationRepresentation([self::TRACKER_ID]),
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }

    public function testFaultWhenTrackerIsNotSuitable(): void
    {
        $saver = SaveConfiguredTrackerStub::build();

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $service_docman = $this->createMock(ServiceDocman::class);
        $handler        = new PUTConfigurationHandler(
            RetrieveArtidocStub::withDocument(
                new ArtidocDocumentInformation(
                    new ArtidocDocument(['item_id' => 1, 'group_id' => self::PROJECT_ID]),
                    $this->createMock(ServiceDocman::class),
                    DocumentServiceDocmanProxy::build($service_docman)
                ),
            ),
            $saver,
            RetrieveTrackerStub::withTracker(
                TrackerTestBuilder::aTracker()
                    ->withUserCanView(true)
                    ->withId(self::TRACKER_ID)
                    ->build(),
            ),
            CheckTrackerIsSuitableForDocumentStub::withoutSuitableTracker(),
        );

        $result = $handler->handle(
            1,
            new ArtidocPUTConfigurationRepresentation([self::TRACKER_ID]),
            $this->user,
        );

        self::assertTrue(Result::isErr($result));
        self::assertFalse($saver->isSaved(1));
    }
}
