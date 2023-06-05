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

namespace Tuleap\WebAuthn\Source;

use Symfony\Component\Uid\Uuid;
use Tuleap\DB\DataAccessObject;
use Webauthn\Exception\InvalidTrustPathException;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\TrustPathLoader;
use function Psl\Encoding\Base64\decode;
use function Psl\Encoding\Base64\encode;
use function Psl\Json\decode as psl_json_decode;
use function Psl\Json\encode as psl_json_encode;

final class WebAuthnCredentialSourceDao extends DataAccessObject implements PublicKeyCredentialSourceRepository, ChangeCredentialSourceName, SaveCredentialSourceWithName, GetAllCredentialSourceByUserId, DeleteCredentialSource
{
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $sql = 'SELECT *
                FROM webauthn_credential_source
                WHERE public_key_credential_id = ?';

        $row = $this->getDB()->row($sql, encode($publicKeyCredentialId));

        if ($row) {
            return $this->mapToPublicKeyCredentialSource($row)->getSource();
        }

        return null;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $sql = 'SELECT *
                FROM webauthn_credential_source
                WHERE user_id = ?';

        $rows = $this->getDB()->q($sql, (int) $publicKeyCredentialUserEntity->getId());

        $res = [];
        foreach ($rows as $row) {
            $res[] = $this->mapToPublicKeyCredentialSource($row)->getSource();
        }

        return $res;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $sql = 'SELECT 1
                FROM webauthn_credential_source
                WHERE public_key_credential_id = ?';

        $time = new \DateTimeImmutable();
        if ($this->getDB()->exists($sql, encode($publicKeyCredentialSource->getPublicKeyCredentialId()))) {
            $this->getDB()->update(
                'webauthn_credential_source',
                [
                    ...$this->mapFromPublicKeyCredentialSource($publicKeyCredentialSource),
                    'last_use' => $time->getTimestamp(),
                ],
                ['public_key_credential_id' => encode($publicKeyCredentialSource->getPublicKeyCredentialId())]
            );
        } else {
            $this->getDB()->insert(
                'webauthn_credential_source',
                [
                    ...$this->mapFromPublicKeyCredentialSource($publicKeyCredentialSource),
                    'created_at' => $time->getTimestamp(),
                    'last_use' => $time->getTimestamp(),
                ]
            );
        }
    }

    // Above, functions from interface PublicKeyCredentialSourceRepository. Used by WebAuthn library
    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.
    // Below, functions for Tuleap usage

    public function saveCredentialSourceWithName(PublicKeyCredentialSource $publicKeyCredentialSource, string $name): void
    {
        $time = new \DateTimeImmutable();
        $this->getDB()->insert(
            'webauthn_credential_source',
            [
                ...$this->mapFromPublicKeyCredentialSource($publicKeyCredentialSource),
                'name' => $name,
                'created_at' => $time->getTimestamp(),
                'last_use' => $time->getTimestamp(),
            ]
        );
    }

    public function changeCredentialSourceName(string $public_key_credential_id, string $name): void
    {
        $this->getDB()->update(
            'webauthn_credential_source',
            ['name' => $name],
            ['public_key_credential_id' => encode($public_key_credential_id)]
        );
    }

    /**
     * @return WebAuthnCredentialSource[]
     */
    public function getAllByUserId(int $user_id): array
    {
        $sql = 'SELECT *
                FROM webauthn_credential_source
                WHERE user_id = ?';

        $rows = $this->getDB()->q($sql, $user_id);

        $res = [];
        foreach ($rows as $row) {
            $res[] = $this->mapToPublicKeyCredentialSource($row);
        }

        return $res;
    }

    public function deleteCredentialSource(string $public_key_credential_id): void
    {
        $this->getDB()->delete(
            'webauthn_credential_source',
            ['public_key_credential_id' => encode($public_key_credential_id)]
        );
    }

    // Above, functions for Tuleap usage
    // _.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-._.-.
    // Below, mapping functions for parsing library entity to database structure and reverse

    /**
     * @return array{
     *     public_key_credential_id: string,
     *     type: string,
     *     transports: string,
     *     attestation_type: string,
     *     trust_path: string,
     *     aaguid: string,
     *     credential_public_key: string,
     *     user_id: int,
     *     counter: int,
     *     other_ui: null|string
     * }
     */
    private function mapFromPublicKeyCredentialSource(PublicKeyCredentialSource $source): array
    {
        return [
            'public_key_credential_id' => encode($source->getPublicKeyCredentialId()),
            'type' => $source->getType(),
            'transports' => psl_json_encode($source->getTransports()),
            'attestation_type' => $source->getAttestationType(),
            'trust_path' => psl_json_encode($source->getTrustPath()),
            'aaguid' => (string) $source->getAaguid(),
            'credential_public_key' => $source->getCredentialPublicKey(),
            'user_id' => (int) $source->getUserHandle(),
            'counter' => $source->getCounter(),
            'other_ui' => $source->getOtherUI() !== null ? psl_json_encode($source->getOtherUI()) : null,
        ];
    }

    /**
     * @param array{
     *     public_key_credential_id: string,
     *     type: string,
     *     transports: string,
     *     attestation_type: string,
     *     trust_path: string,
     *     aaguid: string,
     *     credential_public_key: string,
     *     user_id: int,
     *     counter: int,
     *     other_ui: null|string,
     *     name: string,
     *     created_at: int,
     *     last_use: int
     * } $data
     * @throws InvalidTrustPathException When trust_path in array is invalid
     */
    private function mapToPublicKeyCredentialSource(array $data): WebAuthnCredentialSource
    {
        return new WebAuthnCredentialSource(
            new PublicKeyCredentialSource(
                decode($data['public_key_credential_id']),
                $data['type'],
                psl_json_decode($data['transports'], true),
                $data['attestation_type'],
                TrustPathLoader::loadTrustPath(psl_json_decode($data['trust_path'], true)),
                Uuid::fromString($data['aaguid']),
                $data['credential_public_key'],
                (string) $data['user_id'],
                $data['counter'],
                $data['other_ui'] !== null ? psl_json_decode($data['other_ui'], true) : null
            ),
            $data['name'],
            new \DateTimeImmutable('@' . $data['created_at']),
            new \DateTimeImmutable('@' . $data['last_use'])
        );
    }
}
