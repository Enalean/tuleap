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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202505131600_clear_sensitive_info_deleted_user extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Clear sensitive information of deleted users';
    }

    public function up(): void
    {
        $this->api->dbh->exec('UPDATE user SET password = NULL, authorized_keys = NULL WHERE status = "D"');
        $this->api->dbh->exec('
            DELETE
                user_lost_password,
                user_access_key,
                user_access_key_scope,
                svn_token,
                `session`,
                feedback,
                rest_authentication_token,
                webauthn_challenge,
                webauthn_credential_source,
                oauth2_authorization_code,
                oauth2_authorization_code_scope,
                oauth2_access_token,
                oauth2_access_token_scope,
                oauth2_refresh_token,
                oauth2_refresh_token_scope,
                user_bookmarks,
                user_preferences
            FROM user
            LEFT JOIN user_lost_password ON (user.user_id = user_lost_password.user_id)
            LEFT JOIN user_access_key ON (user.user_id = user_access_key.user_id)
            LEFT JOIN user_access_key_scope ON (user_access_key.id = user_access_key_scope.access_key_id)
            LEFT JOIN svn_token ON (user.user_id = svn_token.user_id)
            LEFT JOIN `session` ON (user.user_id = `session`.user_id)
            LEFT JOIN feedback ON (`session`.id = feedback.session_id)
            LEFT JOIN rest_authentication_token ON (user.user_id = rest_authentication_token.user_id)
            LEFT JOIN webauthn_challenge ON (user.user_id = webauthn_challenge.user_id)
            LEFT JOIN webauthn_credential_source ON (user.user_id = webauthn_credential_source.user_id)
            LEFT JOIN oauth2_authorization_code ON (user.user_id = oauth2_authorization_code.user_id)
            LEFT JOIN oauth2_authorization_code_scope ON (oauth2_authorization_code.id = oauth2_authorization_code_scope.auth_code_id)
            LEFT JOIN oauth2_access_token ON (oauth2_authorization_code.id = oauth2_access_token.authorization_code_id)
            LEFT JOIN oauth2_access_token_scope ON (oauth2_access_token.id = oauth2_access_token_scope.access_token_id)
            LEFT JOIN oauth2_refresh_token ON (oauth2_authorization_code.id = oauth2_refresh_token.authorization_code_id)
            LEFT JOIN oauth2_refresh_token_scope ON oauth2_refresh_token.id = oauth2_refresh_token_scope.refresh_token_id
            LEFT JOIN user_bookmarks ON (user.user_id = user_bookmarks.user_id)
            LEFT JOIN user_preferences ON (user.user_id = user_preferences.user_id)
            WHERE user.status = "D"
        ');
    }
}
