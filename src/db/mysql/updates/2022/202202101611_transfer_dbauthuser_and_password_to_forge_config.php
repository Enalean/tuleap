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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202202101611_transfer_dbauthuser_and_password_to_forge_config extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Transfer sys_dbauth_user and sys_dbauth_password from local.inc to database forgeconfig';
    }

    public function up(): void
    {
        $sth = $this->api->dbh->prepare("REPLACE INTO forgeconfig (name, value) VALUES (:name, :value)");

        $vars = $this->getLocalIncVars();
        if (isset($vars[\Tuleap\DB\DBAuthUserConfig::USER])) {
            $sth->execute(['name' => \Tuleap\DB\DBAuthUserConfig::USER, 'value' => $vars[\Tuleap\DB\DBAuthUserConfig::USER]]);
        }

        if (isset($vars[\Tuleap\DB\DBAuthUserConfig::PASSWORD])) {
            $value = base64_encode(
                \Tuleap\Cryptography\Symmetric\SymmetricCrypto::encrypt(
                    new \Tuleap\Cryptography\ConcealedString($vars[\Tuleap\DB\DBAuthUserConfig::PASSWORD]),
                    (new \Tuleap\Cryptography\KeyFactory())->getEncryptionKey(),
                )
            );
            $sth->execute(['name' => \Tuleap\DB\DBAuthUserConfig::PASSWORD, 'value' => $value]);
        }
    }

    private function getLocalIncVars(): array
    {
        include('/etc/tuleap/conf/local.inc');
        return get_defined_vars();
    }
}
