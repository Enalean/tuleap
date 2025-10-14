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

namespace Tuleap\Test\Builders\SeatManagement;

use DateTimeImmutable;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Eddsa;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\UnencryptedToken;
use LogicException;
use Ramsey\Uuid\Rfc4122\UuidV7;
use function Psl\File\write;
use function Psl\Json\encode as json_encode;

final class PublicKeyTestBuilder
{
    private bool $valid_signature = true;
    private bool $has_kid_header  = true;
    private bool $has_aud_claim   = true;

    private ?UnencryptedToken $token = null;

    /**
     * @param non-empty-string $license_file_path
     * @param non-empty-string $keys_directory
     */
    public function __construct(private readonly string $license_file_path, private readonly string $keys_directory)
    {
    }

    public function withInvalidSignature(): self
    {
        $this->valid_signature = false;
        return $this;
    }

    public function withoutKidHeader(): self
    {
        $this->has_kid_header = false;
        return $this;
    }

    public function withoutAudClaim(): self
    {
        $this->has_aud_claim = false;
        return $this;
    }

    /**
     * @return non-empty-string
     */
    public function build(): string
    {
        $key_pair = sodium_crypto_sign_keypair();
        $today    = new DateTimeImmutable();
        $kid      = UuidV7::uuid7($today)->toString();

        $jwk = [
            'kid' => $kid,
            'kty' => 'OKP',
            'alg' => 'EdDSA',
            'crv' => 'Ed25519',
            'x'   => sodium_bin2base64(sodium_crypto_sign_publickey($key_pair), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
        ];

        $key_file = $this->keys_directory . "/$kid.key";
        write($key_file, json_encode($jwk));

        $raw_private_key = sodium_crypto_sign_secretkey($key_pair);
        assert($raw_private_key !== '');
        $private_key   = InMemory::plainText($raw_private_key);
        $token_builder = new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        if ($this->has_kid_header) {
            $token_builder = $token_builder->withHeader('kid', $kid);
        }
        if ($this->has_aud_claim) {
            $token_builder = $token_builder->permittedFor(hash('sha256', (string) idn_to_ascii('example.com:443', IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46)));
        }
        $expire_at        = $today->modify('+1 day');
        $raw_private_key2 = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());
        assert($raw_private_key2 !== '');
        $this->token = $token_builder
            ->issuedBy('enalean-tuleap-enterprise')
            ->expiresAt($expire_at)
            ->issuedAt($today)
            ->canOnlyBeUsedAfter($today)
            ->identifiedBy(UuidV7::uuid7($expire_at)->toString())
            ->withClaim('restrictions', ['active_user_count' => 500, 'additional_accepted_user_count' => 10])
            ->getToken(
                new Eddsa(),
                $this->valid_signature ? $private_key : InMemory::plainText($raw_private_key2),
            );

        write($this->license_file_path, $this->token->toString());

        return $key_file;
    }

    public function getToken(): UnencryptedToken
    {
        if ($this->token === null) {
            throw new LogicException('Please call build() before getToken()');
        }

        return $this->token;
    }
}
