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
final class b202304031615_create_webauthn_challenge_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create webauthn_challenge table';
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE webauthn_challenge (
            user_id INT NOT NULL PRIMARY KEY,
            challenge BLOB NOT NULL,
            expiration_date INT UNSIGNED NOT NULL,
            INDEX idx_expiration_date (expiration_date)
        ) ENGINE=InnoDB;';

        $this->api->createTable('webauthn_registration_options', $sql);
    }
}
