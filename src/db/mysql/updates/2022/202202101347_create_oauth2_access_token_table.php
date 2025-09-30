<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202202101347_create_oauth2_access_token_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add oauth2_access_token table';
    }

    public function up(): void
    {
        $sql = 'CREATE TABLE oauth2_access_token (
                    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    authorization_code_id INT(11) NOT NULL,
                    verifier VARCHAR(255) NOT NULL,
                    expiration_date INT(11) UNSIGNED NOT NULL,
                    INDEX idx_expiration_date (expiration_date),
                    INDEX idx_authorization_code (authorization_code_id)
                ) ENGINE=InnoDB;';

        $this->api->createTable('oauth2_access_token', $sql);
    }
}
