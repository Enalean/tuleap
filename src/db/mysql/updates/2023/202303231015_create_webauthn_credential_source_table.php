<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
final class b202303231015_create_webauthn_credential_source_table extends Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create webauthn_credential_source table';
    }

    public function up(): void
    {
        $sql = "CREATE TABLE webauthn_credential_source (
            public_key_credential_id VARCHAR(255) NOT NULL PRIMARY KEY,
            type VARCHAR(32) NOT NULL,
            transports TEXT NOT NULL,
            attestation_type VARCHAR(32) NOT NULL,
            trust_path TEXT NOT NULL,
            aaguid VARCHAR(64) NOT NULL,
            credential_public_key TEXT NOT NULL,
            user_id INT NOT NULL,
            counter INT UNSIGNED NOT NULL,
            other_ui TEXT,
            name VARCHAR(255) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_use DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id)
        ) ENGINE=InnoDB;";

        $this->api->createTable('webauthn_credential_source', $sql);
    }
}
