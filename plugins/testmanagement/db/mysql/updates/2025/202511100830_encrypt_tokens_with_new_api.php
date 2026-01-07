<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202511100830_encrypt_tokens_with_new_api extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Re-encrypt campaign job tokens using the current Tuleap cryptography API';
    }

    public function up(): void
    {
        $this->api->dbh->exec('UPDATE plugin_testmanagement_campaign SET encrypted_job_token = NULL WHERE encrypted_job_token = ""');
        $this->api->reencrypt2025ContentWithTheCurrentCryptographyAPI(
            'plugin_testmanagement_campaign',
            'artifact_id',
            'encrypted_job_token'
        );
    }
}
