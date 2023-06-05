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
use Tuleap\WebAuthn\Source\DeleteCredentialSource;
use Tuleap\WebAuthn\Source\GetAllCredentialSourceByUserId;
use Tuleap\WebAuthn\Source\SaveCredentialSourceWithName;
use Tuleap\WebAuthn\Source\WebAuthnCredentialSource;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\TrustPathLoader;

final class WebAuthnCredentialSourceDaoStub implements PublicKeyCredentialSourceRepository, ChangeCredentialSourceName, SaveCredentialSourceWithName, GetAllCredentialSourceByUserId, DeleteCredentialSource
{
    /**
     * @var array<string, string>
     */
    public array $sources_name                 = [];
    private ?PublicKeyCredentialSource $source = null;

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

    public function withRealSource(PublicKeyCredentialSource $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        if ($this->source !== null && $this->source->getPublicKeyCredentialId() === $publicKeyCredentialId) {
            return $this->source;
        }

        if (in_array($publicKeyCredentialId, $this->sources_id)) {
            return new PublicKeyCredentialSource(
                $publicKeyCredentialId,
                'public-key',
                [],
                'attestationType',
                TrustPathLoader::loadTrustPath(['type' => 'Webauthn\\TrustPath\\EmptyTrustPath']),
                Uuid::v4(),
                'credentialPublicKey',
                'user_id',
                0
            );
        }

        return null;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return array_map(
            static fn(string $source_id) => new PublicKeyCredentialSource(
                $source_id,
                'public-key',
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

    public function getAllByUserId(int $user_id): array
    {
        $time = new \DateTimeImmutable();
        return array_map(
            static fn(PublicKeyCredentialSource $source) => new WebAuthnCredentialSource(
                $source,
                'name',
                $time,
                $time
            ),
            $this->findAllForUserEntity(new PublicKeyCredentialUserEntity('', '', ''))
        );
    }

    public function deleteCredentialSource(string $public_key_credential_id): void
    {
        if ($this->source !== null && $this->source->getPublicKeyCredentialId() === $public_key_credential_id) {
            $this->source = null;
        } else {
            $this->sources_id = array_filter(
                $this->sources_id,
                static fn($source_id) => $source_id !== $public_key_credential_id
            );
        }
    }
}
