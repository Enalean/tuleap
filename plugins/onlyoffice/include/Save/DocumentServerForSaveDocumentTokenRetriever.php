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
     */
    public function getServerFromSaveToken(SaveDocumentTokenData $save_token_information): DocumentServer
    {
        $server = $this->servers_retriever->retrieveById($save_token_information->server_id->toString());

        if (! $server->has_existing_secret) {
            throw new DocumentServerHasNoExistingSecretException();
        }

        return $server;
    }
}
