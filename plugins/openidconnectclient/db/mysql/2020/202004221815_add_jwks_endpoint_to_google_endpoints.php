<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202004221815_add_jwks_endpoint_to_google_endpoints extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add column JWKS endpoint to the Google provider entries';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'UPDATE plugin_openidconnectclient_provider_generic
                SET jwks_endpoint="https://www.googleapis.com/oauth2/v3/certs"
                WHERE authorization_endpoint="https://accounts.google.com/o/oauth2/v2/auth" AND token_endpoint="https://oauth2.googleapis.com/token"
                    AND user_info_endpoint="https://www.googleapis.com/oauth2/v3/userinfo"';
        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding the JWKS to the Google provides entries'
            );
        }
    }
}
