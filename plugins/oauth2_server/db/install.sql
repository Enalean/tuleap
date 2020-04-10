/*
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

CREATE TABLE plugin_oauth2_server_app(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    project_id int(11) NOT NULL,
    name VARCHAR(255) NOT NULL,
    redirect_endpoint TEXT NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    use_pkce BOOLEAN NOT NULL,
    INDEX idx_project_id(project_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_authorization_code(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    app_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    has_already_been_used BOOLEAN NOT NULL,
    pkce_code_challenge BINARY(32),
    oidc_nonce TEXT,
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_app_id (app_id),
    INDEX idx_user_app_id (user_id, app_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_authorization_code_scope (
    auth_code_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (auth_code_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_authorization(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    app_id INT(11) NOT NULL,
    UNIQUE (user_id, app_id),
    INDEX idx_app_id (app_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_authorization_scope(
    authorization_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (authorization_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_access_token (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    authorization_code_id INT(11) NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_authorization_code (authorization_code_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_access_token_scope (
    access_token_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (access_token_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_refresh_token (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    authorization_code_id INT(11) NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    has_already_been_used BOOLEAN NOT NULL,
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_authorization_code (authorization_code_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_refresh_token_scope (
    refresh_token_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (refresh_token_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_oidc_signing_key (
    enforce_one_row_table ENUM('SHOULD_HAVE_AT_MOST_ONE_ROW') NOT NULL PRIMARY KEY DEFAULT 'SHOULD_HAVE_AT_MOST_ONE_ROW',
    public_key TEXT NOT NULL,
    private_key BLOB NOT NULL
) ENGINE=InnoDB;