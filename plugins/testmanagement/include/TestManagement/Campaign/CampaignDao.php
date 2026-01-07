<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;

class CampaignDao extends DataAccessObject
{
    public function update(int $campaign_id, string $job_url, ConcealedString $job_token): void
    {
        if (! $job_url) {
            $this->getDB()->delete('plugin_testmanagement_campaign', ['artifact_id' => $campaign_id]);
        }

        $sql = 'REPLACE INTO plugin_testmanagement_campaign (artifact_id, job_url, encrypted_job_token)
                VALUES (?, ?, ?)';

        $this->getDB()->run(
            $sql,
            $campaign_id,
            $job_url,
            $this->encryptDataToStoreInATableRow(
                $job_token,
                $this->getTokenEncryptionAdditionalData($campaign_id)
            ),
        );
    }

    /**
     * @return array{job_url:string,job_token:ConcealedString|null}|null
     */
    public function searchByCampaignId(int $campaign_id): ?array
    {
        $sql = 'SELECT job_url, encrypted_job_token FROM plugin_testmanagement_campaign
                WHERE artifact_id = ?';

        $row = $this->getDB()->row($sql, $campaign_id);

        if ($row === null) {
            return null;
        }

        $token = null;
        if ($row['encrypted_job_token'] !== null) {
            $token = $this->decryptDataStoredInATableRow(
                $row['encrypted_job_token'],
                $this->getTokenEncryptionAdditionalData($campaign_id)
            );
        }

        return ['job_url' => $row['job_url'], 'job_token' => $token];
    }

    private function getTokenEncryptionAdditionalData(int $campaign_id): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData(
            'plugin_testmanagement_campaign',
            'encrypted_job_token',
            (string) $campaign_id
        );
    }
}
