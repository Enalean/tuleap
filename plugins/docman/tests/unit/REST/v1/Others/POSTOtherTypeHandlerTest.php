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

namespace Tuleap\Docman\REST\v1\Others;

use Docman_ItemFactory;
use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Metadata\CustomMetadataException;
use Tuleap\Docman\REST\v1\CopyItem\DocmanCopyItemRepresentation;
use Tuleap\Docman\REST\v1\CopyItem\DocmanValidateRepresentationForCopy;
use Tuleap\Docman\REST\v1\CreatedItemRepresentation;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\REST\v1\DocmanItemsRequest;
use Tuleap\Docman\REST\v1\Folders\ItemCanHaveSubItemsChecker;
use Tuleap\Docman\REST\v1\Metadata\HardCodedMetadataException;
use Tuleap\Docman\Stubs\CopyItemStub;
use Tuleap\Docman\Stubs\CreateOtherTypeItemStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class POSTOtherTypeHandlerTest extends TestCase
{
    private const FOLDER_ID = 101;

    private const ITEM_TO_COPY_ID = 102;
    private const PASTED_ITEM_ID  = 103;
    private const CREATED_ITEM_ID = 104;

    private DocmanItemsEventAdder&MockObject $event_adder;
    private Docman_ItemFactory&MockObject $item_factory;
    private \Docman_PermissionsManager&MockObject $permissions_manager;
    private \PFUser $user;
    private \Project $project;

    public function setUp(): void
    {
        $this->event_adder  = $this->createMock(DocmanItemsEventAdder::class);
        $this->item_factory = $this->createMock(Docman_ItemFactory::class);

        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->project = ProjectTestBuilder::aProject()->build();

        $this->permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        \Docman_PermissionsManager::setInstance((int) $this->project->getID(), $this->permissions_manager);
    }

    public function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    public function testCopyHappyPath(): void
    {
        $pasted_item_representation = CreatedItemRepresentation::build(self::PASTED_ITEM_ID);

        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::withCreatedItemRepresentation($pasted_item_representation),
            static fn() => CreateOtherTypeItemStub::shouldNotBeCalled(),
        );

        $post_representation                = new DocmanOtherTypePOSTRepresentation();
        $post_representation->copy          = new DocmanCopyItemRepresentation();
        $post_representation->copy->item_id = self::ITEM_TO_COPY_ID;

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Folder(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects($this->once())
            ->method('userCanWrite')
            ->willReturn(true);

        $this->event_adder
            ->expects($this->once())
            ->method('addLogEvents');

        $this->event_adder
            ->expects($this->once())
            ->method('addNotificationEvents');

        $result = $post_handler->handle($item_request, $post_representation);
        self::assertSame($pasted_item_representation, $result);
    }

    public function testCopyThrowsFaultWhenInANonFolder(): void
    {
        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::shouldNotBeCalled(),
            static fn() => CreateOtherTypeItemStub::shouldNotBeCalled(),
        );

        $post_representation                = new DocmanOtherTypePOSTRepresentation();
        $post_representation->copy          = new DocmanCopyItemRepresentation();
        $post_representation->copy->item_id = self::ITEM_TO_COPY_ID;

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Empty(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects(self::never())
            ->method('userCanWrite');

        $this->event_adder
            ->expects(self::never())
            ->method('addLogEvents');

        $this->event_adder
            ->expects(self::never())
            ->method('addNotificationEvents');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $post_handler->handle($item_request, $post_representation);
    }

    public function testCopyThrowsExceptionWhenUserIsNotAllowed(): void
    {
        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::shouldNotBeCalled(),
            static fn() => CreateOtherTypeItemStub::shouldNotBeCalled(),
        );

        $post_representation                = new DocmanOtherTypePOSTRepresentation();
        $post_representation->copy          = new DocmanCopyItemRepresentation();
        $post_representation->copy->item_id = self::ITEM_TO_COPY_ID;

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Folder(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects($this->once())
            ->method('userCanWrite')
            ->willReturn(false);

        $this->event_adder
            ->expects(self::never())
            ->method('addLogEvents');

        $this->event_adder
            ->expects(self::never())
            ->method('addNotificationEvents');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $post_handler->handle($item_request, $post_representation);
    }

    public function testCreateItemHappyPath(): void
    {
        $created_item_representation = CreatedItemRepresentation::build(self::CREATED_ITEM_ID);

        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::shouldNotBeCalled(),
            static fn() => CreateOtherTypeItemStub::withCreatedItemRepresentation($created_item_representation),
        );

        $post_representation        = new DocmanOtherTypePOSTRepresentation();
        $post_representation->title = 'New doc';
        $post_representation->type  = 'whatever';

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Folder(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects($this->once())
            ->method('userCanWrite')
            ->willReturn(true);

        $this->event_adder
            ->expects($this->once())
            ->method('addLogEvents');

        $this->event_adder
            ->expects($this->once())
            ->method('addNotificationEvents');

        $result = $post_handler->handle($item_request, $post_representation);
        self::assertSame($created_item_representation, $result);
    }

    public function testCreateItemThrowsFaultWhenInANonFolder(): void
    {
        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::shouldNotBeCalled(),
            static fn() => CreateOtherTypeItemStub::shouldNotBeCalled(),
        );

        $post_representation        = new DocmanOtherTypePOSTRepresentation();
        $post_representation->title = 'New doc';
        $post_representation->type  = 'whatever';

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Empty(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects(self::never())
            ->method('userCanWrite');

        $this->event_adder
            ->expects(self::never())
            ->method('addLogEvents');

        $this->event_adder
            ->expects(self::never())
            ->method('addNotificationEvents');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $post_handler->handle($item_request, $post_representation);
    }

    public function testCreateItemThrowsFaultWhenUserIsNotAllowed(): void
    {
        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::shouldNotBeCalled(),
            static fn() => CreateOtherTypeItemStub::shouldNotBeCalled(),
        );

        $post_representation        = new DocmanOtherTypePOSTRepresentation();
        $post_representation->title = 'New doc';
        $post_representation->type  = 'whatever';

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Folder(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects($this->once())
            ->method('userCanWrite')
            ->willReturn(false);

        $this->event_adder
            ->expects(self::never())
            ->method('addLogEvents');

        $this->event_adder
            ->expects(self::never())
            ->method('addNotificationEvents');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $post_handler->handle($item_request, $post_representation);
    }

    public function testCreateItemThrowsFaultWhenHardCodedMetadataException(): void
    {
        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::shouldNotBeCalled(),
            static fn() => CreateOtherTypeItemStub::withHardCodedMetadataException(HardCodedMetadataException::itemStatusIsInvalid('done')),
        );

        $post_representation        = new DocmanOtherTypePOSTRepresentation();
        $post_representation->title = 'New doc';
        $post_representation->type  = 'whatever';

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Folder(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects($this->once())
            ->method('userCanWrite')
            ->willReturn(true);

        $this->event_adder
            ->expects($this->once())
            ->method('addLogEvents');

        $this->event_adder
            ->expects($this->once())
            ->method('addNotificationEvents');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $post_handler->handle($item_request, $post_representation);
    }

    public function testCreateItemThrowsFaultWhenCustomMetadataException(): void
    {
        $post_handler = new POSTOtherTypeHandler(
            new ItemCanHaveSubItemsChecker(),
            new DocmanValidateRepresentationForCopy(),
            $this->event_adder,
            static fn() => CopyItemStub::shouldNotBeCalled(),
            static fn() => CreateOtherTypeItemStub::withCustomMetadataException(CustomMetadataException::metadataNotFound('plop')),
        );

        $post_representation        = new DocmanOtherTypePOSTRepresentation();
        $post_representation->title = 'New doc';
        $post_representation->type  = 'whatever';

        $item_request = new DocmanItemsRequest(
            $this->item_factory,
            new \Docman_Folder(['item_id' => self::FOLDER_ID]),
            $this->project,
            $this->user,
        );

        $this->permissions_manager
            ->expects($this->once())
            ->method('userCanWrite')
            ->willReturn(true);

        $this->event_adder
            ->expects($this->once())
            ->method('addLogEvents');

        $this->event_adder
            ->expects($this->once())
            ->method('addNotificationEvents');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $post_handler->handle($item_request, $post_representation);
    }
}
