<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\WebAuthn;

use CBOR\ByteStringObject;
use CBOR\IndefiniteLengthByteStringObject;
use CBOR\IndefiniteLengthMapObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Cose\Algorithms;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psl\Hash\Algorithm;
use Symfony\Component\Uid\Uuid;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPathLoader;
use function Psl\Encoding\Base64\encode;
use function Psl\Json\encode as psl_json_encode;

/**
 * This class aims to simulate behavior of a passkey
 */
final class PasskeyStub
{
    private string $key = 'none';

    public function __construct(
        private readonly string $passkey_id = 'passkey id',
        private readonly string $relying_party = 'https://example.com',
    ) {
    }

    public function generateAttestationResponse(string $challenge): array
    {
        $crypted_challenge = $challenge;
        $client_data_json  = Base64UrlSafe::encodeUnpadded(psl_json_encode([
            'type' => 'webauthn.create',
            'challenge' => Base64UrlSafe::encodeUnpadded($crypted_challenge),
            'origin' => $this->relying_party,
        ]));

        $relying_party_id_hash = hex2bin(\Psl\Hash\hash(parse_url($this->relying_party)['host'] ?? 'example.com', Algorithm::SHA256));
        $flags                 = hex2bin((string) 0b01000101);
        $sign_count            = '0000';

        $aaguid                   = Uuid::v4()->toBinary();
        $credential_id_length     = pack('n', (string) 16);
        $credential_id            = 'credential-id123';
        $credential_public_key    = (string) MapObject::create()
            ->add(TextStringObject::create('kty'), TextStringObject::create('EC2'))
            ->add(TextStringObject::create('alg'), NegativeIntegerObject::create(Algorithms::COSE_ALGORITHM_ES256));
        $attested_credential_data = $aaguid . $credential_id_length . $credential_id . $credential_public_key;

        // see https://www.w3.org/TR/webauthn-2/#attestation-object
        $attestation_object = IndefiniteLengthMapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create(AttestationStatement::TYPE_NONE))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(
                TextStringObject::create('authData'),
                IndefiniteLengthByteStringObject::create()
                    // see https://www.w3.org/TR/webauthn-2/#authenticator-data
                    ->append($relying_party_id_hash)
                    ->append($flags)
                    ->append($sign_count)
                    ->append($attested_credential_data)
            );

        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->passkey_id),
            'rawId' => encode($this->passkey_id),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $client_data_json,
                'attestationObject' => encode((string) $attestation_object),
            ],
        ];
    }

    public function generateAssertionResponse(string $challenge): array
    {
        $crypted_challenge     = $challenge;
        $client_data_json      = Base64UrlSafe::encodeUnpadded(psl_json_encode([
            'type' => 'webauthn.get',
            'challenge' => Base64UrlSafe::encodeUnpadded($crypted_challenge),
            'origin' => $this->relying_party,
        ]));
        $relying_party_id_hash = hex2bin(\Psl\Hash\hash(parse_url($this->relying_party)['host'] ?? 'example.com', Algorithm::SHA256));
        $flags                 = (string) 0b0000101;
        $sign_count            = '0000';
        // see https://www.w3.org/TR/webauthn-2/#authenticator-data
        $authenticator_data = $relying_party_id_hash . $flags . $sign_count;

        if ($this->key !== 'none') {
            $private_key = sodium_crypto_sign_secretkey($this->key);
            $signature   = encode(sodium_crypto_sign('', $private_key));
        } else {
            $signature = encode('signature');
        }

        return [
            'id' => Base64UrlSafe::encodeUnpadded($this->passkey_id),
            'rawId' => encode($this->passkey_id),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $client_data_json,
                'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticator_data),
                'signature' => $signature,
            ],
        ];
    }

    public function getCredentialSource(string $user_id): PublicKeyCredentialSource
    {
        $this->key  = sodium_crypto_sign_keypair();
        $public_key = sodium_crypto_sign_publickey($this->key);

        return new PublicKeyCredentialSource(
            $this->passkey_id,
            'public-key',
            [],
            'attestationType',
            TrustPathLoader::loadTrustPath(['type' => 'Webauthn\\TrustPath\\EmptyTrustPath']),
            Uuid::v4(),
            (string) MapObject::create() // see https://www.rfc-editor.org/rfc/rfc9053#section-7
                ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(6)) // crv => Ed25519
                ->add(NegativeIntegerObject::create(-2), ByteStringObject::create($public_key)) // x-coordinate
                ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(1)) // kty => OKP
                ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(Algorithms::COSE_ALGORITHM_EDDSA)), // alg
            $user_id,
            0
        );
    }
}
