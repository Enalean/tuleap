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
 *
 */

DROP TABLE IF EXISTS ai_crosstracker_completion_thread;
CREATE TABLE ai_crosstracker_completion_thread (
    id              BINARY(16)       NOT NULL PRIMARY KEY,
    user_id         INT UNSIGNED     NOT NULL,
    widget_id       INT UNSIGNED     NOT NULL
) ENGINE = InnoDB;

DROP TABLE IF EXISTS ai_crosstracker_completion_message;
CREATE TABLE ai_crosstracker_completion_message (
    id                BINARY(16)       NOT NULL PRIMARY KEY,
    thread_id         BINARY(16)       NOT NULL,
    role              VARCHAR(32)      NOT NULL,
    date              INT UNSIGNED     NOT NULL,
    content           MEDIUMTEXT       NOT NULL,
    tokens_prompt     INT UNSIGNED     NOT NULL DEFAULT 0,
    tokens_completion INT UNSIGNED     NOT NULL DEFAULT 0,
    tokens_total      INT UNSIGNED     NOT NULL DEFAULT 0,
    INDEX idx_thread(thread_id)
) ENGINE = InnoDB;
