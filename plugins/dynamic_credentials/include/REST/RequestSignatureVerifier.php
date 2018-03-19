<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\REST;

use Tuleap\Cryptography\Asymmetric\AsymmetricCrypto;
use Tuleap\Cryptography\Asymmetric\SignaturePublicKey;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidSignatureException;
use Tuleap\DynamicCredentials\Plugin\PluginInfo;

class RequestSignatureVerifier
{
    /**
     * @var SignaturePublicKey
     */
    private $signature_public_key;

    /**
     * @throws \Tuleap\Cryptography\Exception\InvalidKeyException
     */
    public function __construct(PluginInfo $plugin_information)
    {
        $this->signature_public_key = new SignaturePublicKey(
            new ConcealedString(
                base64_decode($plugin_information->getPropertyValueForName('signature_public_key'))
            )
        );
    }

    public function isSignatureValid($signature, ...$request_parameters)
    {
        $message_to_verify = \ForgeConfig::get('sys_https_host') . implode('', $request_parameters);

        $decoded_signature = base64_decode($signature, true);
        if ($decoded_signature === false) {
            return false;
        }
        try {
            $is_signature_valid = AsymmetricCrypto::verify($message_to_verify, $this->signature_public_key, $decoded_signature);
        } catch (InvalidSignatureException $ex) {
            return false;
        }

        return $is_signature_valid;
    }
}
