<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerNotFoundException;
use Tuleap\OnlyOffice\DocumentServer\IRetrieveDocumentServers;

final class DocumentServerForSaveDocumentTokenRetriever
{
    public function __construct(
        private IRetrieveDocumentServers $servers_retriever,
    ) {
    }

    /**
     * @throws DocumentServerNotFoundException
     * @throws DocumentServerHasNoExistingSecretException
     * @throws NoDocumentServerException
     */
    public function getServerFromSaveToken(SaveDocumentTokenData $save_token_information): DocumentServer
    {
        if ($this->shouldWeTakeTheFirstServer($save_token_information)) {
            $servers = $this->servers_retriever->retrieveAll();
            if (empty($servers)) {
                throw new NoDocumentServerException();
            }

            $server = $servers[0];
        } else {
            $server = $this->servers_retriever->retrieveById($save_token_information->server_id);
        }

        if (! $server->has_existing_secret) {
            throw new DocumentServerHasNoExistingSecretException();
        }

        return $server;
    }

    private function shouldWeTakeTheFirstServer(SaveDocumentTokenData $save_token_information): bool
    {
        /*
         * If a document is open before the move from forgeconfig to plugin_onlyoffice_document_server (during 14.4),
         * Then its save token is not tied to a specific server
         * Then we should take the first server instead of rejecting it
         */
        return $save_token_information->server_id === 0;
    }
}
