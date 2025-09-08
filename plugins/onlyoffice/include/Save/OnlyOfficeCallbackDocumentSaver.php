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

use Docman_File;
use Docman_FileStorage;
use Docman_ItemFactory;
use Docman_LockFactory;
use Docman_PermissionsManager;
use Docman_VersionFactory;
use DocmanPlugin;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Docman\Version\CoAuthorDao;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\User\RetrieveUserById;

final class OnlyOfficeCallbackDocumentSaver implements SaveOnlyOfficeCallbackDocument
{
    public function __construct(
        private RetrieveUserById $user_retriever,
        private Docman_ItemFactory $item_factory,
        private Docman_VersionFactory $version_factory,
        private Docman_LockFactory $lock_factory,
        private Docman_FileStorage $docman_file_storage,
        private CoAuthorDao $co_author_dao,
        private PostUpdateFileHandler $post_update_file_handler,
        private \Http\Client\HttpAsyncClient $http_client,
        private RequestFactoryInterface $http_request_factory,
        private DBTransactionExecutor $db_transaction_executor,
    ) {
    }

    #[\Override]
    public function saveDocument(
        SaveDocumentTokenData $save_token_information,
        Option $optional_response_data,
    ): Ok|Err {
        return $optional_response_data->mapOr(
            /** @psalm-return Ok<null>|Err<Fault> */
            function (OnlyOfficeCallbackSaveResponseData $response_data) use ($save_token_information): OK|Err {
                $async_http_response = $this->http_client->sendAsyncRequest(
                    $this->http_request_factory->createRequest('GET', $response_data->download_url)
                );
                return $this->db_transaction_executor->execute(
                    /** @psalm-return Ok<null>|Err<Fault> */
                    function () use ($save_token_information, $response_data, $async_http_response): Ok|Err {
                        return $this->makeSureNewVersionCanBeSaved($save_token_information)
                            ->andThen(
                            /** @psalm-return Ok<null>|Err<Fault> */
                                function (NewFileVersionToCreate $new_file_version_to_create) use ($response_data, $async_http_response): Ok|Err {
                                    return $this->saveDownloadedDocument(
                                        $async_http_response,
                                        $new_file_version_to_create,
                                        $response_data->onlyoffice_server_version,
                                        $response_data->author_ids,
                                    );
                                }
                            );
                    }
                );
            },
            Result::ok(null)
        );
    }

    /**
     * @return Ok<NewFileVersionToCreate>|Err<Fault>
     */
    private function makeSureNewVersionCanBeSaved(SaveDocumentTokenData $save_token_information): Ok|Err
    {
        $user = $this->user_retriever->getUserById($save_token_information->user_id);
        if ($user === null) {
            return Result::err(Fault::fromMessage(sprintf('Cannot retrieve user #%d', $save_token_information->user_id)));
        }

        $document = $this->item_factory->getItemFromDb($save_token_information->document_id);
        if ($document === null) {
            return Result::err(Fault::fromMessage(sprintf('Cannot retrieve document #%d', $save_token_information->document_id)));
        }

        if (! $document->accept(new DoesItemHasExpectedTypeVisitor(Docman_File::class))) {
            return Result::err(Fault::fromMessage(sprintf('Document #%d is not a file', $save_token_information->document_id)));
        }
        assert($document instanceof Docman_File);

        $permissions_manager = Docman_PermissionsManager::instance($document->getGroupId());
        if (! $permissions_manager->userCanWrite($user, $save_token_information->document_id)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'User %s (#%d) cannot write document #%d',
                    $user->getUserName(),
                    $user->getId(),
                    $save_token_information->document_id,
                )
            ));
        }

        if ($this->lock_factory->itemIsLocked($document)) {
            return Result::err(Fault::fromMessage(
                sprintf(
                    'Item #%d is currently locked, saving a possibly collaboratively edited document is not supported in this situation',
                    $save_token_information->document_id,
                )
            ));
        }

        return Result::ok(new NewFileVersionToCreate($user, $document));
    }

    /**
     * @psalm-param list<int> $user_editor_ids
     */
    private function saveDownloadedDocument(
        \Http\Promise\Promise $async_http_response,
        NewFileVersionToCreate $new_file_version_to_create,
        string $onlyoffice_version,
        array $user_editor_ids,
    ): Ok|Err {
        $item = $new_file_version_to_create->item;

        $http_response = $async_http_response->wait();
        assert($http_response instanceof  ResponseInterface);

        $response_status_code = $http_response->getStatusCode();
        if ($response_status_code !== 200) {
            return Result::err(
                Fault::fromMessage(
                    sprintf('Cannot download ONLYOFFICE document, got %d HTTP status code', $response_status_code)
                )
            );
        }

        $response_content = $http_response->getBody()->getContents();
        $file_size        = strlen($response_content);
        $max_file_size    = \ForgeConfig::getInt(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
        if ($file_size >= $max_file_size) {
            return Result::err(
                Fault::fromMessage(
                    sprintf('Document edited in ONLYOFFICE is bigger (%d bytes) than the maximum allowed size (%s bytes)', $file_size, $max_file_size)
                )
            );
        }

        $next_version_id = $this->version_factory->getNextVersionNumber($item);
        $item_id         = $item->getId();

        $file_path = $this->docman_file_storage->store(
            $response_content,
            $item->getGroupId(),
            $item_id,
            $next_version_id,
        );

        if ($file_path === false) {
            return Result::err(Fault::fromMessage(sprintf('Cannot save file for document #%d updated via ONLYOFFICE', $item_id)));
        }

        $primary_user = $new_file_version_to_create->user;
        $version      = $new_file_version_to_create->item->getCurrentVersion();

        $id_version = $this->version_factory->create(
            [
                'item_id'        => $item_id,
                'number'         => $next_version_id,
                'user_id'        => $primary_user->getId(),
                'changelog'      => '',
                'authoring_tool' => sprintf('ONLYOFFICE %s', $onlyoffice_version),
                'filename'       => $version->getFilename(),
                'filesize'       => strlen($response_content),
                'filetype'       => $version->getFiletype(),
                'path'           => $file_path,
            ]
        );

        if (! $id_version) {
            $this->docman_file_storage->delete($file_path);
            return Result::err(Fault::fromMessage(sprintf('Cannot create a new version for document #%d updated via ONLYOFFICE', $item_id)));
        }

        $co_authors = $this->determineCoAuthors($primary_user, $user_editor_ids);
        if (count($co_authors) > 0) {
            $this->co_author_dao->saveVersionCoAuthors((int) $id_version, $co_authors);
        }

        $this->item_factory->update(['id' => $item_id]);

        $this->post_update_file_handler->triggerPostUpdateEvents($item, $primary_user);

        return Result::ok(null);
    }

    /**
     * @psalm-param list<int> $author_ids
     */
    private function determineCoAuthors(\PFUser $primary_user, array $author_ids): array
    {
        $primary_user_id = (int) $primary_user->getId();
        $co_author_ids   = [];
        foreach ($author_ids as $author_id) {
            if ($primary_user_id === $author_id) {
                continue;
            }
            $editor = $this->user_retriever->getUserById($author_id);
            if ($editor !== null) {
                $co_author_ids[] = $author_id;
            }
        }

        return $co_author_ids;
    }
}
