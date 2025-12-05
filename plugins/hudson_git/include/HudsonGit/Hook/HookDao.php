<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;

class HookDao extends DataAccessObject
{
    public function delete(int $repository_id): void
    {
        $sql = 'DELETE FROM plugin_hudson_git_server WHERE repository_id = ?';

        $this->getDB()->run($sql, $repository_id);
    }

    public function save(int $id, string $jenkins_server, ConcealedString $token, bool $is_commit_reference_needed): void
    {
        $sql = 'REPLACE INTO plugin_hudson_git_server(repository_id, jenkins_server_url, encrypted_token, is_commit_reference_needed)
                VALUES(?, ?, ?, ?)';

        $encrypted_token = $this->encryptDataToStoreInATableRow($token, $this->getTokenEncryptionAdditionalData($id));

        $this->getDB()->run($sql, $id, $jenkins_server, $encrypted_token, $is_commit_reference_needed ? 1 : 0);
    }

    /**
     * @psalm-return array{jenkins_server_url: string, is_commit_reference_needed:0|1, token:ConcealedString|null}|null
     */
    public function searchById(int $id): ?array
    {
        $sql = 'SELECT jenkins_server_url, is_commit_reference_needed, encrypted_token
                FROM plugin_hudson_git_server
                WHERE repository_id = ?';

        $row = $this->getDB()->row($sql, $id);
        if ($row === null) {
            return null;
        }
        $row['token'] = null;
        if ($row['encrypted_token'] !== null) {
            $row['token'] = $this->decryptDataStoredInATableRow($row['encrypted_token'], $this->getTokenEncryptionAdditionalData($id));
        }
        unset($row['encrypted_token']);
        return $row;
    }

    private function getTokenEncryptionAdditionalData(int $id): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData('plugin_hudson_git_server', 'encrypted_token', (string) $id);
    }
}
