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

CREATE TABLE plugin_oauth2_access_token_scope (
    access_token_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (access_token_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE plugin_oauth2_refresh_token_scope (
    refresh_token_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (refresh_token_id, scope_key)
) ENGINE=InnoDB;
