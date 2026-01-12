<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Reference;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;
use Tuleap\DB\UUID;

/**
 * @psalm-type BugzillaReferenceRow = array{id:UUID, keyword:string, server:string, username:string, encrypted_api_key:ConcealedString, are_followup_private:bool, rest_url:string}
 */
class Dao extends DataAccessObject
{
    public function save(string $keyword, string $server, string $username, ConcealedString $api_key, bool $are_followups_private, string $rest_api_url): void
    {
        $sql_save = "INSERT INTO plugin_bugzilla_reference(id, keyword, server, username, api_key, encrypted_api_key, are_followup_private, rest_url)
                      VALUES (?, ?, ?, ?, '', ?, ?, ?)";

        $id = $this->uuid_factory->buildUUIDBytes();

        $this->getDB()->run(
            $sql_save,
            $id,
            $keyword,
            $server,
            $username,
            $this->encryptDataToStoreInATableRow($api_key, $this->getAPIKeyEncryptionAdditionalData($id)),
            $are_followups_private,
            $rest_api_url
        );
    }

    /**
     * @psalm-return BugzillaReferenceRow[]
     */
    public function searchAllReferences(): array
    {
        $result = $this->getDB()->run(
            'SELECT id, keyword, server, username, api_key, encrypted_api_key, has_api_key_always_been_encrypted, are_followup_private, rest_url
                       FROM plugin_bugzilla_reference'
        );

        $rows = [];

        foreach ($result as $row) {
            $row['id']      = $this->uuid_factory->buildUUIDFromBytesData($row['id']);
            $row['api_key'] = $this->transformAndReEncryptAPIKey($row['id'], new ConcealedString($row['api_key']), new ConcealedString($row['encrypted_api_key']));
            unset($row['encrypted_api_key']);

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @psalm-return BugzillaReferenceRow|null
     */
    public function searchReferenceByKeyword(string $keyword): ?array
    {
        $sql = 'SELECT id, keyword, server, username, api_key, encrypted_api_key, has_api_key_always_been_encrypted, are_followup_private, rest_url
                FROM plugin_bugzilla_reference WHERE keyword = ?';

        $row = $this->getDB()->row($sql, $keyword);
        if ($row === null) {
            return null;
        }
        $row['id']      = $this->uuid_factory->buildUUIDFromBytesData($row['id']);
        $row['api_key'] = $this->transformAndReEncryptAPIKey($row['id'], new ConcealedString($row['api_key']), new ConcealedString($row['encrypted_api_key']));
        unset($row['encrypted_api_key']);
        return $row;
    }

    public function edit(string $uuid_hex, string $server, string $username, ConcealedString $api_key, bool $are_followups_private, string $rest_api_url): void
    {
        $this->uuid_factory->buildUUIDFromHexadecimalString($uuid_hex)->apply(
            fn(UUID $uuid) => $this->getDB()->tryFlatTransaction(function () use ($uuid, $server, $rest_api_url, $username, $api_key, $are_followups_private): void {
                $id = $uuid->getBytes();
                $this->getDB()->run(
                    'UPDATE plugin_bugzilla_reference SET
                    server = ?,
                    rest_url = ?,
                    username = ?,
                    are_followup_private = ?
                    WHERE id = ?',
                    $server,
                    $rest_api_url,
                    $username,
                    $are_followups_private,
                    $id
                );

                if (! $api_key->isIdenticalTo(new ConcealedString(''))) {
                    $this->getDB()->run(
                        'UPDATE plugin_bugzilla_reference
                        SET api_key = "", has_api_key_always_been_encrypted = true, encrypted_api_key = ?
                        WHERE id = ?',
                        $this->encryptDataToStoreInATableRow($api_key, $this->getAPIKeyEncryptionAdditionalData($id)),
                        $id
                    );
                }

                $link = $server . '/show_bug.cgi?id=$1';

                $this->getDB()->run(
                    "UPDATE reference AS ref
                    INNER JOIN plugin_bugzilla_reference AS bz ON (
                        bz.keyword = ref.keyword
                        AND ref.nature = 'bugzilla'
                        AND scope = 'S'
                    )
                SET ref.link = ?
                WHERE bz.id = ?",
                    $link,
                    $uuid->getBytes()
                );
            })
        );
    }

    private function transformAndReEncryptAPIKey(UUID $uuid, ConcealedString $api_key, ConcealedString $encrypted_api_key): ConcealedString
    {
        $id              = $uuid->getBytes();
        $additional_data = $this->getAPIKeyEncryptionAdditionalData($id);
        if (! $api_key->isIdenticalTo(new ConcealedString(''))) {
            $this->getDB()->run(
                'UPDATE plugin_bugzilla_reference SET api_key = "", encrypted_api_key = ? WHERE id = ?',
                $this->encryptDataToStoreInATableRow($api_key, $additional_data),
                $id,
            );
            return $api_key;
        }
        return $this->decryptDataStoredInATableRow($encrypted_api_key->getString(), $additional_data);
    }

    public function delete(string $uuid_hex): void
    {
        $this->uuid_factory->buildUUIDFromHexadecimalString($uuid_hex)
            ->apply(
                function (UUID $uuid): void {
                    $sql = "DELETE bugzilla, source_ref, target_ref, reference, reference_group
                            FROM plugin_bugzilla_reference AS bugzilla
                                LEFT JOIN cross_references AS source_ref ON (
                                    source_ref.source_type = 'bugzilla' AND source_ref.source_keyword = bugzilla.keyword
                                )
                                LEFT JOIN cross_references AS target_ref ON (
                                    target_ref.target_type = 'bugzilla' AND target_ref.target_keyword = bugzilla.keyword
                                )
                                LEFT JOIN reference ON (
                                    reference.keyword = bugzilla.keyword
                                    AND reference.nature = 'bugzilla'
                                    AND reference.scope = 'S'
                                )
                                LEFT JOIN reference_group ON (
                                    reference.id = reference_group.reference_id
                                )
                            WHERE bugzilla.id = ?";

                    $this->getDB()->run($sql, $uuid->getBytes());
                }
            );
    }

    /**
     * @param non-empty-string $id
     */
    private function getAPIKeyEncryptionAdditionalData(string $id): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData('plugin_bugzilla_reference', 'encrypted_api_key', $id);
    }
}
