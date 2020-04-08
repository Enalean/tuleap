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

use Lcobucci\JWT\Signer\Key;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
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

    public function __construct(KeyFactory $key_factory, OpenIDConnectSigningKeyDAO $dao)
    {
        $this->key_factory = $key_factory;
        $this->dao         = $dao;
    }

    public function getKey(): Key
    {
        $encryption_key = $this->key_factory->getEncryptionKey();

        $encrypted_jwt_private_key = $this->dao->searchEncryptedPrivateKey();

        if ($encrypted_jwt_private_key === null) {
            $rsa_key = \openssl_pkey_new(
                [
                    'digest_alg'       => 'sha256',
                    'private_key_bits' => 4096,
                    'private_key_type' => OPENSSL_KEYTYPE_RSA
                ]
            );
            \openssl_pkey_export($rsa_key, $rsa_private_key_pem_format_str);
            $encrypted_jwt_private_key = SymmetricCrypto::encrypt(new ConcealedString($rsa_private_key_pem_format_str), $encryption_key);
            \sodium_memzero($rsa_private_key_pem_format_str);
            $rsa_public_key = \openssl_pkey_get_details($rsa_key)['key'];
            \openssl_pkey_free($rsa_key);

            $this->dao->save($rsa_public_key, $encrypted_jwt_private_key);
        }

        return new Key(SymmetricCrypto::decrypt($encrypted_jwt_private_key, $encryption_key)->getString());
    }
}
