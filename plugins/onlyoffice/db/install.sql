/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

CREATE TABLE plugin_onlyoffice_download_document_token(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED DEFAULT NULL,
    user_id INT(11) NOT NULL,
    document_id INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE plugin_onlyoffice_save_document_token(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED DEFAULT NULL,
    user_id INT(11) NOT NULL,
    document_id INT(11) UNSIGNED NOT NULL,
    server_id INT(11) NOT NULL DEFAULT 0,
    INDEX idx_document_server_id(document_id, server_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_onlyoffice_document_server(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    url VARCHAR(255) NOT NULL,
    secret_key TEXT NOT NULL,
    is_project_restricted BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB;


CREATE TABLE plugin_onlyoffice_document_server_project_restriction(
    project_id INT(11) NOT NULL,
    server_id INT(11) NOT NULL,
    PRIMARY KEY (project_id, server_id),
    UNIQUE idx_project_id(project_id)
) ENGINE=InnoDB;