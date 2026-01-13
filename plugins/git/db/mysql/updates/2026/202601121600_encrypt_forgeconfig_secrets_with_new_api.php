<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactoryFromFileSystem;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\ForgeUpgrade\LegacyCryptography2025\EncryptionKey;

final class b202601121600_encrypt_forgeconfig_secrets_with_new_api extends \Tuleap\ForgeUpgrade\Bucket // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function description(): string
    {
        return 'Re-encrypt ForgeConfig secrets using the current Tuleap cryptography API';
    }

    public function up(): void
    {
        $legacy_encryption_key = EncryptionKey::build();
        if ($legacy_encryption_key === null) {
            $this->log->warning('Not able to find the encryption key, not re-encrypting values of secrets ForgeConfig keys. Please investigate, it will cause issues later on.');
            return;
        }

        $current_encryption_key = (new KeyFactoryFromFileSystem())->getEncryptionKey();

        $this->api->dbh->beginTransaction();

        $update_statement = $this->api->dbh->prepare('UPDATE forgeconfig SET value = ? WHERE name = ?');

        $rows = $this->api->dbh->query('SELECT name, value FROM forgeconfig WHERE name IN ("mistral_api_key","fts_meilisearch_api_key","mediawiki_standalone_shared_secret","csrf_token_signing_key","email_relayhost_smtp_password") FOR UPDATE');
        foreach ($rows as $row) {
            $name = $row['name'];

            try {
                $update_statement->execute([
                    \sodium_bin2base64(SymmetricCrypto::encrypt(
                        new ConcealedString($legacy_encryption_key->decrypt(\sodium_base642bin($row['value'], SODIUM_BASE64_VARIANT_ORIGINAL))),
                        new EncryptionAdditionalData('forgeconfig', 'value', $name),
                        $current_encryption_key
                    ), SODIUM_BASE64_VARIANT_ORIGINAL),
                    $name,
                ]);
            } catch (\RuntimeException | \SodiumException $exception) {
                $this->log->warning(
                    sprintf(
                        'Could not decrypt ForgeConfig key %s, skipping. Please investigate, it will cause issues later on.',
                        $name,
                    ),
                    ['exception' => $exception]
                );
                continue;
            }
            \sodium_memzero($row['value']);
        }
        $this->api->dbh->commit();
    }
}
