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

namespace Tuleap\Artidoc\Document;

use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Artidoc\Stubs\Document\SearchArtidocDocumentStub;
use Tuleap\Docman\ServiceDocman;
use Tuleap\Docman\Stubs\GetItemFromRowStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Plugin\IsProjectAllowedToUsePluginStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class ArtidocRetrieverTest extends TestCase
{
    private const PROJECT_ID = 101;
    private const ITEM_ID    = 12;

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

    public function testFaultWhenIdIsNotFound(): void
    {
        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()),
            SearchArtidocDocumentStub::withoutResults(),
            GetItemFromRowStub::withVoid(),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenItemIsVoid(): void
    {
        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()),
            SearchArtidocDocumentStub::withResults(['group_id' => self::PROJECT_ID]),
            GetItemFromRowStub::withVoid(),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenItemIsNull(): void
    {
        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()),
            SearchArtidocDocumentStub::withResults(['group_id' => self::PROJECT_ID]),
            GetItemFromRowStub::withNull(),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenItemIsNotAnArtidoc(): void
    {
        $row = ['group_id' => self::PROJECT_ID];

        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()),
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem(new \Docman_File($row)),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenItemIsNotReadable(): void
    {
        $row = ['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID];

        $this->permissions_manager->method('userCanRead')->willReturn(false);

        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWithoutProject(),
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem(new ArtidocDocument($row)),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenItemIsInAnInvalidProject(): void
    {
        $row = ['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID];

        $this->permissions_manager->method('userCanRead')->willReturn(true);

        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWithoutProject(),
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem(new ArtidocDocument($row)),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenProjectIsNotAllowed(): void
    {
        $row = ['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID];

        $this->permissions_manager->method('userCanRead')->willReturn(true);

        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()),
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem(new ArtidocDocument($row)),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsNotAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenProjectDoesNotHaveTrackerService(): void
    {
        $row = ['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID];

        $this->permissions_manager->method('userCanRead')->willReturn(true);

        $project = ProjectTestBuilder::aProject()
            ->withoutServices()
            ->build();

        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith($project),
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem(new ArtidocDocument($row)),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testFaultWhenProjectDoesNotHaveDocmanService(): void
    {
        $row = ['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID];

        $this->permissions_manager->method('userCanRead')->willReturn(true);

        $service_tracker = $this->createMock(\ServiceTracker::class);
        $service_tracker->method('getShortName')->willReturn(\trackerPlugin::SERVICE_SHORTNAME);

        $project = ProjectTestBuilder::aProject()
            ->withServices(
                $service_tracker,
            )
            ->build();

        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith($project),
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem(new ArtidocDocument($row)),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->retrieveArtidoc(123, $this->user)));
    }

    public function testHappyPath(): void
    {
        $row = ['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID];

        $this->permissions_manager->method('userCanRead')->willReturn(true);

        $service_tracker = $this->createMock(\ServiceTracker::class);
        $service_tracker->method('getShortName')->willReturn(\trackerPlugin::SERVICE_SHORTNAME);

        $service_docman = $this->createMock(ServiceDocman::class);
        $service_docman->method('getShortName')->willReturn(\DocmanPlugin::SERVICE_SHORTNAME);

        $project = ProjectTestBuilder::aProject()
            ->withServices(
                $service_tracker,
                $service_docman,
            )
            ->build();

        $item = new ArtidocDocument($row);

        $retriever = new ArtidocRetriever(
            ProjectByIDFactoryStub::buildWith($project),
            SearchArtidocDocumentStub::withResults($row),
            GetItemFromRowStub::withItem($item),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        $result = $retriever->retrieveArtidoc(123, $this->user);
        self::assertTrue(Result::isOk($result));
        self::assertSame($item, $result->value->document);
        self::assertSame($service_docman, $result->value->not_yet_hexagonal_service_docman);
    }
}
