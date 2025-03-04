<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Docman_FileStorage;
use Docman_ItemFactory;
use Docman_VersionFactory;
use Http\Client\HttpAsyncClient;
use Http\Client\Promise\HttpFulfilledPromise;
use Psr\Http\Message\RequestInterface;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\User\RetrieveUserById;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeCallbackDocumentSaverTest extends TestCase
{
    use ForgeConfigSandbox;

    private const PROJECT_ID = 102;
    /**
     * @var \Docman_PermissionsManager&\PHPUnit\Framework\MockObject\Stub
     */
    private $permissions_manager;
    /**
     * @var Docman_ItemFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $item_factory;
    /**
     * @var Docman_VersionFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $version_factory;
    /**
     * @var Docman_FileStorage&\PHPUnit\Framework\MockObject\Stub
     */
    private $file_storage;
    /**
     * @var CoAuthorDao&\PHPUnit\Framework\MockObject\Stub
     */
    private $co_author_dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissions_manager = $this->createStub(\Docman_PermissionsManager::class);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $this->permissions_manager);

        $this->item_factory    = $this->createStub(Docman_ItemFactory::class);
        $this->version_factory = $this->createStub(Docman_VersionFactory::class);
        $this->file_storage    = $this->createStub(Docman_FileStorage::class);
        $this->co_author_dao   = $this->createStub(CoAuthorDao::class);
    }

    protected function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    public function testDoesNothingWhenNoOOResponseDataAvailable(): void
    {
        $saver  = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );
        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::nothing(OnlyOfficeCallbackSaveResponseData::class),
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testSaveDocumentEditedInOnlyOffice(): void
    {
        \ForgeConfig::set('plugin_docman_max_file_size', 10);
        $docman_file = new \Docman_File();
        $docman_file->setCurrentVersion(new \Docman_Version(['id' => 1]));
        $docman_file->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);
        $this->item_factory->method('update');

        $this->permissions_manager->method('userCanWrite')->willReturn(true);
        $this->version_factory->method('getNextVersionNumber')->willReturn(2);
        $this->version_factory->method('create')->willReturn(2);

        $this->file_storage->method('store')->willReturn('/path');

        $this->co_author_dao->method('saveVersionCoAuthors');

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [105, 106])),
        );

        self::assertTrue(Result::isOk($result));
    }

    public function testReturnsAnErrorWhenCannotCreateNewVersion(): void
    {
        \ForgeConfig::set('plugin_docman_max_file_size', 10);
        $docman_file = new \Docman_File();
        $docman_file->setCurrentVersion(new \Docman_Version(['id' => 1]));
        $docman_file->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $this->permissions_manager->method('userCanWrite')->willReturn(true);
        $this->version_factory->method('getNextVersionNumber')->willReturn(2);
        $this->version_factory->method('create')->willReturn(false);

        $this->file_storage->method('store')->willReturn('/path');
        $this->file_storage->method('delete');

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenCannotCreateStoreFile(): void
    {
        \ForgeConfig::set('plugin_docman_max_file_size', 10);
        $docman_file = new \Docman_File();
        $docman_file->setCurrentVersion(new \Docman_Version(['id' => 1]));
        $docman_file->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $this->permissions_manager->method('userCanWrite')->willReturn(true);
        $this->version_factory->method('getNextVersionNumber')->willReturn(2);

        $this->file_storage->method('store')->willReturn(false);

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenEditedFileIsBiggerThanTheMaxAllowedSize(): void
    {
        \ForgeConfig::set('plugin_docman_max_file_size', 0);
        $docman_file = new \Docman_File();
        $docman_file->setCurrentVersion(new \Docman_Version(['id' => 1]));
        $docman_file->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenCannotDownloadEditedDocument(): void
    {
        $docman_file = new \Docman_File();
        $docman_file->setCurrentVersion(new \Docman_Version(['id' => 1]));
        $docman_file->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
            false,
            500,
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenUserDoesHaveThePermissionsToWriteTheFile(): void
    {
        $docman_file = new \Docman_File();
        $docman_file->setCurrentVersion(new \Docman_Version(['id' => 1]));
        $docman_file->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $this->permissions_manager->method('userCanWrite')->willReturn(false);

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenDocumentIsLocked(): void
    {
        $docman_file = new \Docman_File();
        $docman_file->setCurrentVersion(new \Docman_Version(['id' => 1]));
        $docman_file->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($docman_file);

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
            true
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenTheItemToUpdateIsNotAFile(): void
    {
        $document = new \Docman_EmbeddedFile();
        $document->setCurrentVersion(new \Docman_Version(['id' => 12]));
        $document->setGroupId(self::PROJECT_ID);
        $this->item_factory->method('getItemFromDb')->willReturn($document);

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenTheItemToUpdateDoesNotExist(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(null);

        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withUser(UserTestBuilder::aUser()->withId(101)->build()),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 101, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testReturnsAnErrorWhenTheUserAssociatedWithTheTokenDoesNotExist(): void
    {
        $saver = $this->buildSaver(
            RetrieveUserByIdStub::withNoUser(),
        );

        $result = $saver->saveDocument(
            new SaveDocumentTokenData(1, 1, 1, new UUIDTestContext()),
            Option::fromValue(new OnlyOfficeCallbackSaveResponseData('https://example.com/download', '7.2.0', [102])),
        );

        self::assertTrue(Result::isErr($result));
    }

    private function buildSaver(
        RetrieveUserById $user_retriever,
        bool $edited_document_is_locked = false,
        int $download_http_status_code = 200,
    ): OnlyOfficeCallbackDocumentSaver {
        $lock_factory = $this->createStub(\Docman_LockFactory::class);
        $lock_factory->method('itemIsLocked')->willReturn($edited_document_is_locked);

        $post_update_handler = $this->createStub(PostUpdateFileHandler::class);
        $post_update_handler->method('triggerPostUpdateEvents');

        return new OnlyOfficeCallbackDocumentSaver(
            $user_retriever,
            $this->item_factory,
            $this->version_factory,
            $lock_factory,
            $this->file_storage,
            $this->co_author_dao,
            $post_update_handler,
            new class ($download_http_status_code) implements HttpAsyncClient {
                public function __construct(private int $http_status_code)
                {
                }

                public function sendAsyncRequest(RequestInterface $request)
                {
                    return new HttpFulfilledPromise(HTTPFactoryBuilder::responseFactory()->createResponse($this->http_status_code));
                }
            },
            HTTPFactoryBuilder::requestFactory(),
            new DBTransactionExecutorPassthrough()
        );
    }
}
