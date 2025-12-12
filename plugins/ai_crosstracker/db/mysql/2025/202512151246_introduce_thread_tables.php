<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
final class b202512151246_introduce_thread_tables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Introduce ai_crosstracker tables';
    }

    public function up(): void
    {
        $this->api->createTable(
            'ai_crosstracker_completion_thread',
            <<<EOS
            CREATE TABLE ai_crosstracker_completion_thread (
                id              BINARY(16)       NOT NULL PRIMARY KEY,
                user_id         INT UNSIGNED     NOT NULL,
                widget_id       INT UNSIGNED     NOT NULL
            ) ENGINE = InnoDB
            EOS
        );

        $this->api->createTable(
            'ai_crosstracker_completion_message',
            <<<EOS
            CREATE TABLE ai_crosstracker_completion_message (
                id              BINARY(16)       NOT NULL PRIMARY KEY,
                thread_id       BINARY(16)       NOT NULL,
                role            VARCHAR(32)      NOT NULL,
                date            INT UNSIGNED     NOT NULL,
                content         MEDIUMTEXT       NOT NULL,
                INDEX idx_thread(thread_id)
            ) ENGINE = InnoDB
            EOS
        );
    }
}
