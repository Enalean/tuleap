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
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\TrustPathLoader;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebAuthnCredentialSourceDaoTest extends TestIntegrationTestCase
{
    private WebAuthnCredentialSourceDao $dao;

    private const USER_ID = 101;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = new WebAuthnCredentialSourceDao();
    }

    public function testSaveAndRetrieve(): void
    {
        $source = $this->generateSource(self::USER_ID);

        // It does not find before saving
        $retrieved = $this->dao->findOneByCredentialId('gbleskefe');
        self::assertNull($retrieved);
        $sources = $this->dao->findAllForUserEntity(
            PublicKeyCredentialUserEntity::create('Marianna Deberry', (string) self::USER_ID, 'mdeberry')
        );
        self::assertEmpty($sources);
        $sources = $this->dao->getAllByUserId(self::USER_ID);
        self::assertEmpty($sources);

        $this->dao->saveCredentialSource($source);

        $retrieved = $this->dao->findOneByCredentialId($source->publicKeyCredentialId);
        self::assertNotNull($retrieved);
        $this->assertSourceEquals($source, $retrieved);

        $sources = $this->dao->getAllByUserId((int) $source->userHandle);
        self::assertCount(1, $sources);
        $retrieved = $sources[0];
        $this->assertSourceEquals($source, $retrieved->getSource());
        self::assertNotNull($retrieved->getName());
        self::assertNotNull($retrieved->getCreatedAt());
        self::assertNotNull($retrieved->getLastUse());

        $retrieved = $this->dao->getCredentialSourceById($source->publicKeyCredentialId);
        self::assertTrue($retrieved->isValue());
        $retrieved = $retrieved->unwrapOr(null);
        $this->assertSourceEquals($source, $retrieved->getSource());
        self::assertNotNull($retrieved->getName());
        self::assertNotNull($retrieved->getCreatedAt());
        self::assertNotNull($retrieved->getLastUse());
    }

    public function testSaveEditNameThenFind(): void
    {
        $source = $this->generateSource(self::USER_ID);

        $this->dao->saveCredentialSource($source);
        $this->dao->changeCredentialSourceName($source->publicKeyCredentialId, 'MyAwesomeKey');

        $sources = $this->dao->getAllByUserId((int) $source->userHandle);
        self::assertCount(1, $sources);
        self::assertSame('MyAwesomeKey', $sources[0]->getName());
    }

    public function testSaveWithNameThenFind(): void
    {
        $source = $this->generateSource(self::USER_ID);

        $this->dao->saveCredentialSourceWithName($source, 'MyAwesomeKey');

        $sources = $this->dao->getAllByUserId((int) $source->userHandle);
        self::assertCount(1, $sources);
        self::assertSame('MyAwesomeKey', $sources[0]->getName());
    }

    public function testDelete(): void
    {
        $source = $this->generateSource(self::USER_ID);
        $this->dao->saveCredentialSource($source);

        $this->dao->deleteCredentialSource($source->publicKeyCredentialId);

        $retrieved = $this->dao->findOneByCredentialId($source->publicKeyCredentialId);
        self::assertNull($retrieved);
    }

    private function assertSourceEquals(PublicKeyCredentialSource $expected, PublicKeyCredentialSource $actual): void
    {
        self::assertSame($expected->publicKeyCredentialId, $actual->publicKeyCredentialId);
        self::assertSame($expected->type, $actual->type);
        self::assertEqualsCanonicalizing($expected->transports, $actual->transports);
        self::assertSame($expected->attestationType, $actual->attestationType);
        self::assertEquals($expected->trustPath, $actual->trustPath);
        self::assertEquals($expected->aaguid, $actual->aaguid);
        self::assertSame($expected->credentialPublicKey, $actual->credentialPublicKey);
        self::assertSame($expected->userHandle, $actual->userHandle);
        self::assertSame($expected->counter, $actual->counter);
        self::assertEquals($expected->otherUI, $actual->otherUI);
    }

    private function generateSource(int $user_id): PublicKeyCredentialSource
    {
        return PublicKeyCredentialSource::create(
            random_bytes(32),
            'type',
            [],
            'attestationType',
            TrustPathLoader::loadTrustPath(['type' => 'Webauthn\\TrustPath\\EmptyTrustPath']),
            Uuid::v4(),
            random_bytes(16),
            (string) $user_id,
            0
        );
    }
}
