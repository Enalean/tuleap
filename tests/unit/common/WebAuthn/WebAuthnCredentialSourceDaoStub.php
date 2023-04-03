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

namespace Tuleap\WebAuthn;

use Symfony\Component\Uid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\TrustPathLoader;

final class WebAuthnCredentialSourceDaoStub implements PublicKeyCredentialSourceRepository
{
    /**
     * @param string[] $sources_id
     */
    private function __construct(
        private readonly array $sources_id,
    ) {
    }

    /**
     * @param string[] $sources_id
     */
    public static function withCredentialSources(array $sources_id): self
    {
        return new self($sources_id);
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
    }
}
