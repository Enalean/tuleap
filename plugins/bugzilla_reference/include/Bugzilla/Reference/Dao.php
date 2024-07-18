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

use Tuleap\DB\DataAccessObject;

/**
 * @psalm-type BugzillaReferenceRow = array{id:int, keyword:string, server:string, username:string, api_key:string, encrypted_api_key:string, has_api_key_always_been_encrypted:bool, are_followup_private:bool, rest_url:string}
 */
class Dao extends DataAccessObject
{
    public function save(string $keyword, string $server, string $username, string $encrypted_api_key, bool $are_followups_private, string $rest_api_url): void
    {
        $sql_save = "INSERT INTO plugin_bugzilla_reference(keyword, server, username, api_key, encrypted_api_key, are_followup_private, rest_url)
                      VALUES (?, ?, ?, '', ?, ?, ?)";

        $this->getDB()->run($sql_save, $keyword, $server, $username, $encrypted_api_key, $are_followups_private, $rest_api_url);
    }

    /**
     * @psalm-return BugzillaReferenceRow[]
     */
    public function searchAllReferences(): array
    {
        return $this->getDB()->run(
            'SELECT id, keyword, server, username, api_key, encrypted_api_key, has_api_key_always_been_encrypted, are_followup_private, rest_url
                       FROM plugin_bugzilla_reference'
        );
    }

    /**
     * @psalm-return BugzillaReferenceRow|null
     */
    public function searchReferenceByKeyword(string $keyword): ?array
    {
        $sql = 'SELECT id, keyword, server, username, api_key, encrypted_api_key, has_api_key_always_been_encrypted, are_followup_private, rest_url
                FROM plugin_bugzilla_reference WHERE keyword = ?';

        return $this->getDB()->row($sql, $keyword);
    }

    public function edit(int $id, string $server, string $username, string $encrypted_api_key, bool $has_api_key_always_been_encrypted, bool $are_followups_private, string $rest_api_url): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($id, $server, $rest_api_url, $username, $encrypted_api_key, $has_api_key_always_been_encrypted, $are_followups_private): void {
            $this->getDB()->run(
                'UPDATE plugin_bugzilla_reference SET
                    server = ?,
                    rest_url = ?,
                    username = ?,
                    api_key = "",
                    encrypted_api_key = ?,
                    has_api_key_always_been_encrypted = ?,
                    are_followup_private = ?
                    WHERE id = ?',
                $server,
                $rest_api_url,
                $username,
                $encrypted_api_key,
                $has_api_key_always_been_encrypted,
                $are_followups_private,
                $id
            );

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
                $id
            );
        });
    }

    /**
     * @psalm-return BugzillaReferenceRow|null
     */
    public function getReferenceById(int $id): ?array
    {
        return $this->getDB()->row(
            'SELECT id, keyword, server, username, api_key, encrypted_api_key, has_api_key_always_been_encrypted, are_followup_private, rest_url
                       FROM plugin_bugzilla_reference WHERE id = ?',
            $id
        );
    }

    public function delete(int $id): void
    {
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

        $this->getDB()->run($sql, $id);
    }
}
