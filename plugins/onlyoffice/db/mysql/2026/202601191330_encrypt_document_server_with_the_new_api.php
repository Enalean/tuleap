<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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
final class b202601191330_encrypt_document_server_with_the_new_api extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Re-encrypt document server keys using the current Tuleap cryptography API';
    }

    public function up(): void
    {
        $this->api->dbh->exec('ALTER TABLE plugin_onlyoffice_document_server MODIFY secret_key BLOB NOT NULL');
        $this->api->dbh->exec('UPDATE plugin_onlyoffice_document_server SET secret_key = FROM_BASE64(secret_key)');
        $this->api->reencrypt2025ContentWithTheCurrentCryptographyAPI(
            'plugin_onlyoffice_document_server',
            'id',
            'secret_key'
        );
    }
}
