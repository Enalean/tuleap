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
final class b202503111145_adjust_email_smtp_tls_configuration extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Migrate setting "email_relayhost_smtp_use_tls" to "email_relayhost_smtp_use_implicit_tls"';
    }

    public function up(): void
    {
        $this->api->dbh->exec('
            INSERT INTO forgeconfig(name, value)
            SELECT "email_relayhost_smtp_use_implicit_tls", value
            FROM forgeconfig
            WHERE name = "email_relayhost_smtp_use_tls" AND
                EXISTS(SELECT 1 FROM forgeconfig WHERE name = "email_relayhost" AND value NOT LIKE "%:587")
        ');
    }
}
