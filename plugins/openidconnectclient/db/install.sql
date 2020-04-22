/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_user_mapping (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(11) UNSIGNED NOT NULL,
    provider_id INT(11) UNSIGNED NOT NULL,
    user_openidconnect_identifier TEXT NOT NULL,
    last_used INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(id),
    INDEX idx_mapping_provider_user(provider_id, user_id)
);

CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_provider (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    client_id TEXT NOT NULL,
    client_secret TEXT NOT NULL,
    unique_authentication_endpoint BOOLEAN DEFAULT FALSE,
    icon VARCHAR(50) NOT NULL,
    color VARCHAR(20) NOT NULL,
    PRIMARY KEY(id)
);

CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_provider_generic (
    provider_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    authorization_endpoint TEXT NOT NULL,
    token_endpoint TEXT NOT NULL,
    jwks_endpoint TEXT NOT NULL,
    user_info_endpoint TEXT NOT NULL
);


CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_provider_azure_ad (
    provider_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    tenant_id TEXT NOT NULL,
    acceptable_tenant_auth_identifier VARCHAR(32) NOT NULL
);

CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_unlinked_account (
    id VARCHAR(32) NOT NULL,
    provider_id INT(11) UNSIGNED NOT NULL,
    openidconnect_identifier TEXT NOT NULL,
    PRIMARY KEY(id)
);

INSERT INTO plugin_openidconnectclient_provider(name, client_id, client_secret, icon, color)
VALUES ('Google', '', '', '', '');

INSERT INTO plugin_openidconnectclient_provider_generic(provider_id, authorization_endpoint, token_endpoint, jwks_endpoint, user_info_endpoint)
VALUES ((SELECT LAST_INSERT_ID() FROM plugin_openidconnectclient_provider),
        'https://accounts.google.com/o/oauth2/v2/auth',
        'https://oauth2.googleapis.com/token',
        'https://www.googleapis.com/oauth2/v3/certs',
        'https://www.googleapis.com/oauth2/v3/userinfo');
