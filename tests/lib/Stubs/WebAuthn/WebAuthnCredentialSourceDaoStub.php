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

use Symfony\Component\Uid\Uuid;
use Tuleap\WebAuthn\Source\ChangeCredentialSourceName;
use Tuleap\WebAuthn\Source\SaveCredentialSourceWithName;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\TrustPathLoader;

final class WebAuthnCredentialSourceDaoStub implements PublicKeyCredentialSourceRepository, ChangeCredentialSourceName, SaveCredentialSourceWithName
{
    /**
     * @var array<string, string>
     */
    public array $sources_name = [];

    /**
     * @param string[] $sources_id
     */
    private function __construct(
        public array $sources_id,
    ) {
    }

    /**
     * @no-named-arguments
     */
    public static function withCredentialSources(string $first_source_id, string ...$other_source_ids): self
    {
        return new self([$first_source_id, ...$other_source_ids]);
    }

    public static function withoutCredentialSources(): self
    {
        return new self([]);
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        return null;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return array_map(
            static fn(string $source_id) => new PublicKeyCredentialSource(
                $source_id,
                'type',
                [],
                'attestationType',
                TrustPathLoader::loadTrustPath(['type' => 'Webauthn\\TrustPath\\EmptyTrustPath']),
                Uuid::v4(),
                'credentialPublicKey',
                'user_id',
                0
            ),
            $this->sources_id
        );
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->sources_id[] = $publicKeyCredentialSource->getPublicKeyCredentialId();
    }

    public function changeCredentialSourceName(string $public_key_credential_id, string $name): void
    {
        $this->sources_name[$public_key_credential_id] = $name;
    }

    public function saveCredentialSourceWithName(PublicKeyCredentialSource $publicKeyCredentialSource, string $name): void
    {
        $this->sources_id[] = $publicKeyCredentialSource->getPublicKeyCredentialId();

        $this->sources_name[$publicKeyCredentialSource->getPublicKeyCredentialId()] = $name;
    }
}
