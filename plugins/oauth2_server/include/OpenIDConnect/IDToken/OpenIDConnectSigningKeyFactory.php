<?php
/**
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

declare(strict_types=1);

namespace Tuleap\OAuth2Server\OpenIDConnect\IDToken;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

class OpenIDConnectSigningKeyFactory
{
    /**
     * @var KeyFactory
     */
    private $key_factory;
    /**
     * @var OpenIDConnectSigningKeyDAO
     */
    private $dao;
    /**
     * @var \DateInterval
     */
    private $signing_key_expiration_delay;
    /**
     * @var \DateInterval
     */
    private $id_token_expiration_delay;

    public function __construct(
        KeyFactory $key_factory,
        OpenIDConnectSigningKeyDAO $dao,
        \DateInterval $signing_key_expiration_delay,
        \DateInterval $id_token_expiration_delay
    ) {
        $this->key_factory                  = $key_factory;
        $this->dao                          = $dao;
        $this->signing_key_expiration_delay = $signing_key_expiration_delay;
        $this->id_token_expiration_delay    = $id_token_expiration_delay;
    }

    /**
     * @return SigningPublicKey[]
     */
    public function getPublicKeys(\DateTimeImmutable $current_time): array
    {
        $pem_public_keys = $this->dao->searchPublicKeys();
        if (empty($pem_public_keys)) {
            $encryption_key  = $this->key_factory->getEncryptionKey();
            $pem_public_key  = $this->generateAndSaveKey($encryption_key, $current_time)->public_key;
            $pem_public_keys = [$pem_public_key];
        }

        $public_keys = [];
        foreach ($pem_public_keys as $pem_public_key) {
            $public_keys[] = SigningPublicKey::fromPEMFormat($pem_public_key);
        }
        return $public_keys;
    }

    public function getKey(\DateTimeImmutable $current_time): SigningPrivateKey
    {
        $encryption_key = $this->key_factory->getEncryptionKey();

        $row = $this->dao->searchMostRecentNonExpiredEncryptedPrivateKey($current_time->getTimestamp());

        if ($row === null) {
            $new_key     = $this->generateAndSaveKey($encryption_key, $current_time);
            $private_key = new SigningPrivateKey(
                SigningPublicKey::fromPEMFormat($new_key->public_key),
                SymmetricCrypto::decrypt($new_key->encrypted_private_key, $encryption_key)
            );
        } else {
            $private_key = new SigningPrivateKey(
                SigningPublicKey::fromPEMFormat($row['public_key']),
                SymmetricCrypto::decrypt($row['private_key'], $encryption_key)
            );
        }

        return $private_key;
    }

    /**
     * @return object{public_key:string, encrypted_private_key:string}
     */
    private function generateAndSaveKey(EncryptionKey $encryption_key, \DateTimeImmutable $current_time): object
    {
        $rsa_key = \openssl_pkey_new(
            [
                'digest_alg'       => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA
            ]
        );
        \openssl_pkey_export($rsa_key, $rsa_private_key_pem_format_str);
        $encrypted_jwt_private_key = SymmetricCrypto::encrypt(new ConcealedString($rsa_private_key_pem_format_str), $encryption_key);
        \sodium_memzero($rsa_private_key_pem_format_str);
        $rsa_public_key = \openssl_pkey_get_details($rsa_key)['key'];
        \openssl_pkey_free($rsa_key);

        $new_key_expiration_date = $current_time->add($this->signing_key_expiration_delay);
        $old_keys_cleanup_date   = $current_time->sub($this->id_token_expiration_delay);

        $this->dao->save(
            $rsa_public_key,
            $encrypted_jwt_private_key,
            $new_key_expiration_date->getTimestamp(),
            $old_keys_cleanup_date->getTimestamp()
        );

        return new /** @psalm-immutable */ class ($rsa_public_key, $encrypted_jwt_private_key)
        {
            /**
             * @var string
             */
            public $public_key;
            /**
             * @var string
             */
            public $encrypted_private_key;

            public function __construct(string $public_key, string $encrypted_private_key)
            {
                $this->public_key            = $public_key;
                $this->encrypted_private_key = $encrypted_private_key;
            }
        };
    }
}
