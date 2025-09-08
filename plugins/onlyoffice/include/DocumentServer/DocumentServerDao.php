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

use ParagonIE\EasyDB\EasyDB;
use ParagonIE\EasyDB\EasyStatement;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\UUID;

final class DocumentServerDao extends DataAccessObject implements IRetrieveDocumentServers, IDeleteDocumentServer, ICreateDocumentServer, IUpdateDocumentServer, IRestrictDocumentServer
{
    public function __construct(private DocumentServerKeyEncryption $encryption)
    {
        parent::__construct();
    }

    /**
     * @return list<DocumentServer>
     */
    #[\Override]
    public function retrieveAll(): array
    {
        return $this->getDB()->tryFlatTransaction(
            fn(): array => $this->retrieveAllWithoutTransaction()
        );
    }

    /**
     * @return list<DocumentServer>
     */
    private function retrieveAllWithoutTransaction(): array
    {
        $document_servers = [];

        $server_restrictions = array_reduce(
            $this->getDB()->run(
                'SELECT R.server_id, R.project_id, `groups`.unix_group_name AS name, `groups`.group_name AS label, `groups`.icon_codepoint
                FROM plugin_onlyoffice_document_server_project_restriction AS R
                INNER JOIN `groups` ON (R.project_id = `groups`.group_id AND `groups`.status = "A")',
            ),
            static function (array $server_restrictions, array $row) {
                if (! isset($server_restrictions[$row['server_id']])) {
                    $server_restrictions[$row['server_id']] = [];
                }

                $server_restrictions[$row['server_id']][$row['project_id']] = RestrictedProject::fromRow($row);

                return $server_restrictions;
            },
            []
        );

        $server_rows = $this->getDB()->run(
            'SELECT id, url, secret_key, is_project_restricted FROM plugin_onlyoffice_document_server ORDER BY url'
        );

        foreach ($server_rows as $server_row) {
            $server_id  = $this->uuid_factory->buildUUIDFromBytesData($server_row['id']);
            $secret_key = new ConcealedString($server_row['secret_key']);
            sodium_memzero($server_row['secret_key']);

            if ($server_row['is_project_restricted'] || count($server_rows) > 1) {
                $document_servers[] = DocumentServer::withProjectRestrictions(
                    $server_id,
                    $server_row['url'],
                    $secret_key,
                    $server_restrictions[$server_id->getBytes()] ?? []
                );
            } else {
                $document_servers[] = DocumentServer::withoutProjectRestrictions(
                    $server_id,
                    $server_row['url'],
                    $secret_key,
                );
            }
        }

        return $document_servers;
    }

    /**
     * @throws DocumentServerNotFoundException
     */
    #[\Override]
    public function retrieveById(string $uuid_hex): DocumentServer
    {
        return $this->uuid_factory->buildUUIDFromHexadecimalString($uuid_hex)->match(
            function (UUID $uuid): DocumentServer {
                $uuid_bytes = $uuid->getBytes();

                $row = $this->getDB()->row(
                    'SELECT url, secret_key, is_project_restricted FROM plugin_onlyoffice_document_server WHERE id = ?',
                    $uuid_bytes
                );
                if (! $row) {
                    throw new DocumentServerNotFoundException();
                }

                $secret_key = new ConcealedString($row['secret_key']);
                sodium_memzero($row['secret_key']);

                if ($row['is_project_restricted'] || $this->isThereMultipleServers()) {
                    $project_restrictions = array_map(
                        static fn(array $row) => RestrictedProject::fromRow($row),
                        $this->getDB()->run(
                            'SELECT R.project_id, `groups`.unix_group_name AS name, `groups`.group_name AS label, `groups`.icon_codepoint
                        FROM plugin_onlyoffice_document_server_project_restriction AS R
                        INNER JOIN `groups` ON (R.project_id = `groups`.group_id AND `groups`.status <> "D")
                        WHERE server_id=?',
                            $uuid_bytes,
                        )
                    );

                    return DocumentServer::withProjectRestrictions(
                        $uuid,
                        $row['url'],
                        $secret_key,
                        $project_restrictions
                    );
                }

                return DocumentServer::withoutProjectRestrictions($uuid, $row['url'], $secret_key);
            },
            fn(): never => throw new DocumentServerNotFoundException()
        );
    }

    private function isThereMultipleServers(): bool
    {
        return $this->getDB()->cell('SELECT COUNT(id) FROM plugin_onlyoffice_document_server') > 1;
    }

    #[\Override]
    public function delete(string $uuid_hex): void
    {
        $this->uuid_factory->buildUUIDFromHexadecimalString($uuid_hex)->apply(
            function (UUID $uuid): void {
                $this->getDB()->run(
                    'DELETE plugin_onlyoffice_document_server.*, plugin_onlyoffice_document_server_project_restriction.*
                                FROM plugin_onlyoffice_document_server
                                LEFT JOIN plugin_onlyoffice_document_server_project_restriction ON (plugin_onlyoffice_document_server.id = plugin_onlyoffice_document_server_project_restriction.server_id)
                                WHERE plugin_onlyoffice_document_server.id = ?',
                    $uuid->getBytes()
                );
            }
        );
    }

    #[\Override]
    public function create(string $url, ConcealedString $secret_key): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($url, $secret_key): void {
                $id = $this->uuid_factory->buildUUIDBytes();
                $db->insert(
                    'plugin_onlyoffice_document_server',
                    [
                        'id' => $id,
                        'url'                   => $url,
                        'secret_key'            => $this->encryption->encryptValue($secret_key),
                        'is_project_restricted' => false,
                    ]
                );
                if ($this->isThereMultipleServers()) {
                    $db->run('UPDATE plugin_onlyoffice_document_server SET is_project_restricted = TRUE');
                }
            }
        );
    }

    #[\Override]
    public function update(string $uuid_hex, string $url, ConcealedString $secret_key): void
    {
        $this->uuid_factory->buildUUIDFromHexadecimalString($uuid_hex)->mapOr(
            fn(UUID $uuid): int => $this->getDB()->update(
                'plugin_onlyoffice_document_server',
                ['url' => $url, 'secret_key' => $this->encryption->encryptValue($secret_key)],
                ['id' => $uuid->getBytes()],
            ),
            null
        );
    }

    #[\Override]
    public function restrict(UUID $id, array $project_ids): void
    {
        $data_to_insert = [];
        foreach ($project_ids as $project_id) {
            $data_to_insert[] = ['server_id' => $id->getBytes(), 'project_id' => $project_id];
        }

        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($id, $data_to_insert): void {
                $id_bytes = $id->getBytes();

                $db->update('plugin_onlyoffice_document_server', ['is_project_restricted' => true], ['id' => $id_bytes]);
                $db->delete('plugin_onlyoffice_document_server_project_restriction', ['server_id' => $id_bytes]);
                if (count($data_to_insert) > 0) {
                    $project_ids_statement = EasyStatement::open()->in(
                        'project_id IN (?*)',
                        array_column($data_to_insert, 'project_id')
                    );
                    $db->safeQuery(
                        "DELETE FROM plugin_onlyoffice_document_server_project_restriction WHERE $project_ids_statement",
                        $project_ids_statement->values()
                    );
                    $db->insertMany('plugin_onlyoffice_document_server_project_restriction', $data_to_insert);
                }
            }
        );
    }

    #[\Override]
    public function unrestrict(UUID $id): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($id): void {
                $nb_servers = $db->single('SELECT COUNT(*) FROM plugin_onlyoffice_document_server');
                if ($nb_servers > 1) {
                    throw new TooManyServersException();
                }

                $id_bytes = $id->getBytes();

                $db->delete('plugin_onlyoffice_document_server_project_restriction', ['server_id' => $id_bytes]);
                $db->update('plugin_onlyoffice_document_server', ['is_project_restricted' => false], ['id' => $id_bytes]);
            }
        );
    }
}
