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

namespace Tuleap\OnlyOffice\DocumentServer;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DataAccessObject;

final class DocumentServerDao extends DataAccessObject implements IRetrieveDocumentServers, IDeleteDocumentServer, ICreateDocumentServer, IUpdateDocumentServer
{
    public function __construct(private DocumentServerKeyEncryption $encryption)
    {
        parent::__construct();
    }

    /**
     * @return list<DocumentServer>
     */
    public function retrieveAll(): array
    {
        return array_map(
            static fn (array $row): DocumentServer => new DocumentServer($row['id'], $row['url'], new ConcealedString($row['secret_key'])),
            $this->getDB()->run('SELECT * FROM plugin_onlyoffice_document_server ORDER BY url')
        );
    }

    /**
     * @throws DocumentServerNotFoundException
     */
    public function retrieveById(int $id): DocumentServer
    {
        $row = $this->getDB()->row('SELECT * FROM plugin_onlyoffice_document_server WHERE id = ?', $id);
        if (! $row) {
            throw new DocumentServerNotFoundException();
        }

        return new DocumentServer($row['id'], $row['url'], new ConcealedString($row['secret_key']));
    }

    public function delete(int $id): void
    {
        $this->getDB()->delete('plugin_onlyoffice_document_server', ['id' => $id]);
    }

    public function create(string $url, ConcealedString $secret_key): void
    {
        $this->getDB()->insert(
            'plugin_onlyoffice_document_server',
            ['url' => $url, 'secret_key' => $this->encryption->encryptValue($secret_key)]
        );
    }

    public function update(int $id, string $url, ConcealedString $secret_key): void
    {
        $this->getDB()->update(
            'plugin_onlyoffice_document_server',
            ['url' => $url, 'secret_key' => $this->encryption->encryptValue($secret_key)],
            ['id' => $id],
        );
    }
}
