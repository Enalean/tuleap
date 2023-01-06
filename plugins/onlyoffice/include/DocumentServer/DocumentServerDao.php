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

final class DocumentServerDao extends DataAccessObject implements IRetrieveDocumentServers, IDeleteDocumentServer, ICreateDocumentServer, IUpdateDocumentServer, IRestrictDocumentServer
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

                $server_restrictions[$row['server_id']][] = RestrictedProject::fromRow($row);

                return $server_restrictions;
            },
            []
        );

        $server_rows = $this->getDB()->run(
            'SELECT id, url, secret_key, is_project_restricted FROM plugin_onlyoffice_document_server ORDER BY url'
        );

        foreach ($server_rows as $server_row) {
            $server_id  = $server_row['id'];
            $secret_key = new ConcealedString($server_row['secret_key']);
            sodium_memzero($server_row['secret_key']);

            if ($server_row['is_project_restricted'] || count($server_rows) > 1) {
                $document_servers[] = DocumentServer::withProjectRestrictions(
                    $server_id,
                    $server_row['url'],
                    $secret_key,
                    $server_restrictions[$server_id] ?? []
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
    public function retrieveById(int $id): DocumentServer
    {
        $row = $this->getDB()->row(
            'SELECT url, secret_key, is_project_restricted FROM plugin_onlyoffice_document_server WHERE id = ?',
            $id
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
                    $id,
                )
            );

            return DocumentServer::withProjectRestrictions(
                $id,
                $row['url'],
                $secret_key,
                $project_restrictions
            );
        }

        return DocumentServer::withoutProjectRestrictions($id, $row['url'], $secret_key);
    }

    private function isThereMultipleServers(): bool
    {
        return $this->getDB()->cell('SELECT COUNT(id) FROM plugin_onlyoffice_document_server') > 1;
    }

    public function delete(int $id): void
    {
        $this->getDB()->run(
            'DELETE plugin_onlyoffice_document_server.*, plugin_onlyoffice_document_server_project_restriction.*
            FROM plugin_onlyoffice_document_server
            LEFT JOIN plugin_onlyoffice_document_server_project_restriction ON (plugin_onlyoffice_document_server.id = plugin_onlyoffice_document_server_project_restriction.server_id)
            WHERE plugin_onlyoffice_document_server.id = ?',
            $id
        );
    }

    public function create(string $url, ConcealedString $secret_key): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($url, $secret_key): void {
                $db->insert(
                    'plugin_onlyoffice_document_server',
                    ['url'                   => $url,
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

    public function update(int $id, string $url, ConcealedString $secret_key): void
    {
        $this->getDB()->update(
            'plugin_onlyoffice_document_server',
            ['url' => $url, 'secret_key' => $this->encryption->encryptValue($secret_key)],
            ['id' => $id],
        );
    }

    public function restrict(int $id, array $project_ids): void
    {
        $data_to_insert = [];
        foreach ($project_ids as $project_id) {
            $data_to_insert[] = ['server_id' => $id, 'project_id' => $project_id];
        }

        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db) use ($id, $data_to_insert): void {
                $db->update('plugin_onlyoffice_document_server', ['is_project_restricted' => true], ['id' => $id]);
                $db->delete('plugin_onlyoffice_document_server_project_restriction', ['server_id' => $id]);
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
}
