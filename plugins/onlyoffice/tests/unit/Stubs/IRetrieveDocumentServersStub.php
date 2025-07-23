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

namespace Tuleap\OnlyOffice\Stubs;

use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\OnlyOffice\DocumentServer\DocumentServerNotFoundException;
use Tuleap\OnlyOffice\DocumentServer\IRetrieveDocumentServers;

final class IRetrieveDocumentServersStub implements IRetrieveDocumentServers
{
    /**
     * @param list<DocumentServer> ...$servers
     */
    private function __construct(private array $servers)
    {
    }

    public static function buildWith(DocumentServer ...$servers): self
    {
        return new self($servers);
    }

    public static function buildWithoutServer(): self
    {
        return new self([]);
    }

    /**
     * @return list<DocumentServer>
     */
    #[\Override]
    public function retrieveAll(): array
    {
        return $this->servers;
    }

    #[\Override]
    public function retrieveById(string $uuid_hex): DocumentServer
    {
        foreach ($this->servers as $server) {
            if ($server->id->toString() === $uuid_hex) {
                return $server;
            }
        }

        throw new DocumentServerNotFoundException();
    }
}
